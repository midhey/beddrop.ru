import { useMemo } from "react";
import { router } from "expo-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { courierApi } from "@/api/courier";
import { canDeliver, canPickup, isShiftOpen } from "@/domain/courier/presentation";
import type { CourierOrder, RouteSegment } from "@/domain/courier/types";
import { useLocationTracking } from "@/hooks/use-location-tracking";
import { useAuth } from "@/store/auth";
import { useCourierWorkdayStore } from "@/store/courier-workday";

export type WorkdayMode = "idle" | "preview" | "toRestaurant" | "toClient";

const distanceForSort = (order: CourierOrder) =>
  order.courier_approach_distance_meters ?? order.delivery_distance_meters ?? Number.MAX_SAFE_INTEGER;

const routeForActiveOrder = (order: CourierOrder | null): RouteSegment[] => {
  if (!order) return [];

  const segments = order.route_segments ?? [];
  if (order.status === "PICKED_UP") {
    return segments.filter((segment) => segment.segment_type === "restaurant_to_client");
  }
  if (order.status === "COURIER_ASSIGNED") {
    return segments.filter((segment) => segment.segment_type === "courier_to_restaurant");
  }

  return [];
};

const pickActiveOrder = (orders: CourierOrder[], selectedOrderId: number | null) => {
  if (!orders.length) return null;
  return orders.find((order) => order.id === selectedOrderId) ?? orders[0] ?? null;
};

export function useCourierWorkday() {
  const queryClient = useQueryClient();
  const logout = useAuth((state) => state.logout);
  const selectedOrderId = useCourierWorkdayStore((state) => state.selectedOrderId);
  const setSelectedOrderId = useCourierWorkdayStore((state) => state.setSelectedOrderId);

  const profile = useQuery({ queryKey: ["courier", "profile"], queryFn: courierApi.profile, retry: false });
  const shift = useQuery({ queryKey: ["courier", "shift"], queryFn: courierApi.currentShift, retry: false });
  const open = isShiftOpen(shift.data);
  const location = useLocationTracking(open);
  const available = useQuery({
    queryKey: ["courier", "orders", "available"],
    queryFn: courierApi.available,
    enabled: open,
    retry: false,
    refetchInterval: open ? 45000 : false,
  });
  const active = useQuery({
    queryKey: ["courier", "orders", "active"],
    queryFn: courierApi.active,
    enabled: open,
    retry: false,
    refetchInterval: open ? 30000 : false,
  });
  const earnings = useQuery({
    queryKey: ["courier", "earnings"],
    queryFn: courierApi.earnings,
    enabled: open,
    retry: false,
  });

  const availableOrders = useMemo(
    () => [...(available.data ?? [])].sort((a, b) => distanceForSort(a) - distanceForSort(b)),
    [available.data],
  );
  const activeOrders = active.data ?? [];
  const activeOrder = pickActiveOrder(activeOrders, selectedOrderId);
  const selectedAvailableOrder = activeOrder
    ? null
    : availableOrders.find((order) => order.id === selectedOrderId) ?? null;
  const currentOrder = activeOrder ?? selectedAvailableOrder;
  const routeSegments = routeForActiveOrder(activeOrder);
  const mode: WorkdayMode = activeOrder
    ? canDeliver(activeOrder)
      ? "toClient"
      : "toRestaurant"
    : selectedAvailableOrder
      ? "preview"
      : "idle";

  const invalidateCourier = async () => {
    await queryClient.invalidateQueries({ queryKey: ["courier"] });
  };

  const toggleShift = useMutation({
    mutationFn: async () => {
      if (open) return courierApi.endShift();
      return courierApi.startShift();
    },
    onSuccess: async () => {
      setSelectedOrderId(null);
      await invalidateCourier();
    },
  });

  const assign = useMutation({
    mutationFn: (orderId: number) => courierApi.assign(orderId),
    onSuccess: async (order) => {
      setSelectedOrderId(order.id);
      await invalidateCourier();
    },
  });

  const pickedUp = useMutation({
    mutationFn: (orderId: number) => courierApi.pickedUp(orderId),
    onSuccess: async (order) => {
      setSelectedOrderId(order.id);
      await invalidateCourier();
    },
  });

  const delivered = useMutation({
    mutationFn: (orderId: number) => courierApi.delivered(orderId),
    onSuccess: async () => {
      setSelectedOrderId(null);
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["courier", "orders"] }),
        queryClient.invalidateQueries({ queryKey: ["courier", "earnings"] }),
      ]);
    },
  });

  const doLogout = async () => {
    setSelectedOrderId(null);
    await logout();
    router.replace("/login");
  };

  const selectOrder = (orderId: number | null) => {
    setSelectedOrderId(orderId);
  };

  return {
    profile: profile.data,
    profileError: profile.isError,
    shift: shift.data,
    openedAt: shift.data?.started_at,
    open,
    location,
    availableOrders,
    activeOrders,
    activeOrder,
    selectedAvailableOrder,
    currentOrder,
    routeSegments,
    mode,
    selectedOrderId,
    earnings: earnings.data,
    loading: profile.isFetching || shift.isFetching || available.isFetching || active.isFetching,
    error: available.error || active.error || profile.error || shift.error,
    actionBusy: toggleShift.isPending || assign.isPending || pickedUp.isPending || delivered.isPending,
    canPickupActive: activeOrder ? canPickup(activeOrder) : false,
    canDeliverActive: activeOrder ? canDeliver(activeOrder) : false,
    selectOrder,
    clearSelectedOrder: () => setSelectedOrderId(null),
    toggleShift: () => toggleShift.mutateAsync(),
    assignOrder: (orderId: number) => assign.mutateAsync(orderId),
    markPickedUp: (orderId: number) => pickedUp.mutateAsync(orderId),
    markDelivered: (orderId: number) => delivered.mutateAsync(orderId),
    logout: doLogout,
  };
}

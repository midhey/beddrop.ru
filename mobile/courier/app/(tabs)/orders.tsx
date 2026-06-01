import { useQuery, useQueryClient } from "@tanstack/react-query";
import { router } from "expo-router";
import { Text } from "react-native";
import { courierApi } from "@/api/courier";
import { OrderCard } from "@/components/order-card";
import { Screen } from "@/components/screen";
import { Card, Muted, Title } from "@/components/ui";
import { canDeliver, canPickup } from "@/domain/courier/presentation";
import { useCourierWorkdayStore } from "@/store/courier-workday";

export default function OrdersScreen() {
  const queryClient = useQueryClient();
  const setSelectedOrderId = useCourierWorkdayStore((state) => state.setSelectedOrderId);
  const available = useQuery({ queryKey: ["courier", "orders", "available"], queryFn: courierApi.available, retry: false, refetchInterval: 45000 });
  const active = useQuery({ queryKey: ["courier", "orders", "active"], queryFn: courierApi.active, retry: false, refetchInterval: 45000 });

  const refresh = async () => {
    await queryClient.invalidateQueries({ queryKey: ["courier"] });
  };

  const openOnMap = (orderId: number) => {
    setSelectedOrderId(orderId);
    router.push("/shift");
  };

  return (
    <Screen>
      <Title>Заказы</Title>
      <Card>
        <Title>Доступные</Title>
        {available.error ? <Muted>Откройте смену, чтобы видеть доступные заказы.</Muted> : null}
        {available.data?.length ? available.data.map((order) => (
          <OrderCard
            key={order.id}
            order={order}
            actionLabel="Взять заказ"
            onPress={() => openOnMap(order.id)}
            onAction={async () => {
              const assigned = await courierApi.assign(order.id);
              setSelectedOrderId(assigned.id);
              await refresh();
              router.push("/shift");
            }}
          />
        )) : <Text selectable>Сейчас нет доступных заказов.</Text>}
      </Card>
      <Card>
        <Title>Активные</Title>
        {active.data?.length ? active.data.map((order) => (
          <OrderCard
            key={order.id}
            order={order}
            active
            onPress={() => openOnMap(order.id)}
            actionLabel={canPickup(order) ? "Заказ забран" : canDeliver(order) ? "Доставлен" : undefined}
            onAction={async () => {
              if (canPickup(order)) {
                const picked = await courierApi.pickedUp(order.id);
                setSelectedOrderId(picked.id);
              }
              if (canDeliver(order)) {
                await courierApi.delivered(order.id);
                setSelectedOrderId(null);
              }
              await refresh();
              router.push("/shift");
            }}
          />
        )) : <Text selectable>У вас пока нет активных заказов.</Text>}
      </Card>
    </Screen>
  );
}

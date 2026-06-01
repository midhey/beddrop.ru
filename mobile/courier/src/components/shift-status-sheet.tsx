import { useCallback, useMemo, useRef, useState } from "react";
import type { ComponentType } from "react";
import { Animated, PanResponder, Pressable, ScrollView, Text, useWindowDimensions, View } from "react-native";
import {
  Bike,
  ChevronUp,
  CircleCheck,
  Clock,
  LogOut,
  MapPin,
  Navigation,
  PackageCheck,
  Star,
  Wallet,
} from "lucide-react-native";
import { Button, Card, Muted, StatusChip } from "@/components/ui";
import {
  courierPayout,
  formatAddress,
  formatDeliveryArea,
  formatDistance,
  formatDuration,
  vehicleLabel,
} from "@/domain/courier/presentation";
import type { CourierEarnings, CourierOrder, CourierProfile, RouteSegment } from "@/domain/courier/types";
import type { WorkdayMode } from "@/hooks/use-courier-workday";
import { colors, iconSizes, iconStrokeWidth, radii, shadows } from "@/theme/tokens";

type SheetIcon = ComponentType<{ color?: string; size?: number; strokeWidth?: number }>;

const COLLAPSED_HEIGHT = 138;

const sumMetric = (segments: RouteSegment[], key: "distance_meters" | "duration_seconds") =>
  segments.reduce((total, segment) => total + (segment[key] ?? 0), 0);

export const formatShiftTime = (value?: string | null) => {
  if (!value) return "неизвестно";

  return new Intl.DateTimeFormat("ru-RU", {
    day: "2-digit",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(value));
};

const ProfileRow = ({ Icon, label, value }: { Icon: SheetIcon; label: string; value: string | number }) => (
  <View
    style={{
      flexDirection: "row",
      alignItems: "center",
      gap: 10,
      paddingVertical: 9,
      paddingHorizontal: 10,
      borderRadius: radii.md,
      backgroundColor: colors.surfaceSoft,
      borderWidth: 1,
      borderColor: colors.borderLight,
    }}
  >
    <Icon color={colors.primary} size={iconSizes.md} strokeWidth={iconStrokeWidth} />
    <View style={{ flex: 1, gap: 1 }}>
      <Text selectable style={{ color: colors.textMuted, fontSize: 11, fontWeight: "800" }}>
        {label}
      </Text>
      <Text selectable style={{ color: colors.textStrong, fontSize: 13, fontWeight: "900" }}>
        {value}
      </Text>
    </View>
  </View>
);

const OrderMiniCard = ({
  order,
  selected,
  onPress,
}: {
  order: CourierOrder;
  selected?: boolean;
  onPress: () => void;
}) => (
  <Pressable onPress={onPress}>
    <View
      style={{
        padding: 12,
        gap: 8,
        borderRadius: radii.lg,
        backgroundColor: selected ? colors.bgAlt : colors.surface,
        borderWidth: 1,
        borderColor: selected ? colors.primary : colors.borderNeutral,
      }}
    >
      <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", gap: 10 }}>
        <View style={{ flex: 1, gap: 2 }}>
          <Text selectable style={{ color: colors.textStrong, fontSize: 15, fontWeight: "900" }}>
            Заказ #{order.id}
          </Text>
          <Muted>{order.restaurant?.name ?? "Ресторан"}</Muted>
        </View>
        <View
          style={{
            flexDirection: "row",
            alignItems: "center",
            gap: 5,
            paddingVertical: 6,
            paddingHorizontal: 9,
            borderRadius: radii.pill,
            backgroundColor: colors.secondary,
          }}
        >
          <Wallet color={colors.textStrong} size={iconSizes.sm} strokeWidth={iconStrokeWidth} />
          <Text selectable style={{ color: colors.textStrong, fontSize: 12, fontWeight: "900" }}>
            {courierPayout(order)}
          </Text>
        </View>
      </View>
      <View style={{ flexDirection: "row", flexWrap: "wrap", gap: 8 }}>
        <Muted>{formatDistance(order.courier_approach_distance_meters ?? order.delivery_distance_meters)}</Muted>
        <Muted>{formatDuration(order.delivery_duration_seconds)}</Muted>
      </View>
    </View>
  </Pressable>
);

const ActionButton = ({
  mode,
  order,
  disabled,
  onAssign,
  onPickedUp,
  onDelivered,
}: {
  mode: WorkdayMode;
  order: CourierOrder | null;
  disabled: boolean;
  onAssign: (orderId: number) => void;
  onPickedUp: (orderId: number) => void;
  onDelivered: (orderId: number) => void;
}) => {
  if (!order || mode === "idle") return null;

  if (mode === "preview") {
    return (
      <Button disabled={disabled} onPress={() => onAssign(order.id)}>
        Взять заказ
      </Button>
    );
  }

  if (mode === "toRestaurant") {
    return (
      <Button disabled={disabled} onPress={() => onPickedUp(order.id)}>
        Заказ забран
      </Button>
    );
  }

  return (
    <Button disabled={disabled} onPress={() => onDelivered(order.id)}>
      Доставлен
    </Button>
  );
};

const titleForMode = (open: boolean, mode: WorkdayMode) => {
  if (!open) return "Готовы выйти?";
  if (mode === "preview") return "Новый заказ";
  if (mode === "toRestaurant") return "Едем к ресторану";
  if (mode === "toClient") return "Едем к клиенту";
  return "Вы на смене";
};

const subtitleForMode = (open: boolean, mode: WorkdayMode, order: CourierOrder | null, availableCount: number, openedAt?: string | null) => {
  if (!open) return "Откройте смену, чтобы получать заказы.";
  if (order) return `Заказ #${order.id} · ${order.restaurant?.name ?? "ресторан"}`;
  if (availableCount > 0) return `${availableCount} доступных заказов рядом`;
  return `Смена открыта ${formatShiftTime(openedAt)}`;
};

export function ShiftStatusSheet({
  open,
  profile,
  profileError,
  openedAt,
  mode,
  availableOrders,
  activeOrder,
  selectedAvailableOrder,
  currentOrder,
  routeSegments,
  earnings,
  busy,
  onSelectOrder,
  onClearSelection,
  onAssign,
  onPickedUp,
  onDelivered,
  onToggleShift,
  onLogout,
}: {
  open: boolean;
  profile: CourierProfile | undefined;
  profileError: boolean;
  openedAt?: string | null;
  mode: WorkdayMode;
  availableOrders: CourierOrder[];
  activeOrder: CourierOrder | null;
  selectedAvailableOrder: CourierOrder | null;
  currentOrder: CourierOrder | null;
  routeSegments: RouteSegment[];
  earnings?: CourierEarnings;
  busy: boolean;
  onSelectOrder: (orderId: number) => void;
  onClearSelection: () => void;
  onAssign: (orderId: number) => void;
  onPickedUp: (orderId: number) => void;
  onDelivered: (orderId: number) => void;
  onToggleShift: () => void;
  onLogout: () => void;
}) {
  const { height } = useWindowDimensions();
  const expandedHeight = Math.min(560, Math.max(410, Math.round(height * 0.68)));
  const collapsedOffset = expandedHeight - COLLAPSED_HEIGHT;
  const [translateY] = useState(() => new Animated.Value(collapsedOffset));
  const currentOffset = useRef(collapsedOffset);
  const startOffset = useRef(collapsedOffset);
  const routeDistance = sumMetric(routeSegments, "distance_meters");
  const routeDuration = sumMetric(routeSegments, "duration_seconds");

  const clamp = useCallback((value: number) => Math.min(collapsedOffset, Math.max(0, value)), [collapsedOffset]);

  const animateTo = useCallback((offset: number) => {
    Animated.spring(translateY, {
      toValue: offset,
      useNativeDriver: true,
      damping: 22,
      stiffness: 220,
      mass: 0.8,
    }).start();
  }, [translateY]);

  const panResponder = useMemo(
    () =>
      // eslint-disable-next-line react-hooks/refs
      PanResponder.create({
        onMoveShouldSetPanResponder: (_event, gesture) => Math.abs(gesture.dy) > 6,
        onPanResponderGrant: () => {
          startOffset.current = currentOffset.current;
        },
        onPanResponderMove: (_event, gesture) => {
          const next = clamp(startOffset.current + gesture.dy);
          currentOffset.current = next;
          translateY.setValue(next);
        },
        onPanResponderRelease: (_event, gesture) => {
          const shouldExpand = gesture.vy < -0.35 || currentOffset.current < collapsedOffset / 2;
          const next = shouldExpand ? 0 : collapsedOffset;
          currentOffset.current = next;
          animateTo(next);
        },
      }),
    [animateTo, clamp, collapsedOffset, translateY],
  );

  const toggleExpanded = () => {
    const next = currentOffset.current === 0 ? collapsedOffset : 0;
    currentOffset.current = next;
    animateTo(next);
  };

  const renderDetails = () => {
    if (!open) {
      return (
        <Card soft compact>
          <Muted>Смена закрыта. Карта останется на последней точке, заказы появятся после выхода на линию.</Muted>
        </Card>
      );
    }

    if (mode === "idle") {
      return (
        <View style={{ gap: 10 }}>
          <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", gap: 8 }}>
            <Text selectable style={{ color: colors.textStrong, fontSize: 16, fontWeight: "900" }}>
              Доступные заказы
            </Text>
            <StatusChip tone={availableOrders.length ? "accent" : "muted"}>{availableOrders.length}</StatusChip>
          </View>
          {availableOrders.length ? (
            availableOrders.map((order) => (
              <OrderMiniCard
                key={order.id}
                order={order}
                selected={order.id === selectedAvailableOrder?.id}
                onPress={() => onSelectOrder(order.id)}
              />
            ))
          ) : (
            <Card soft compact>
              <Muted>Сейчас нет доступных заказов. Оставьте смену открытой, список обновится автоматически.</Muted>
            </Card>
          )}
        </View>
      );
    }

    if (!currentOrder) return null;

    return (
      <View style={{ gap: 10 }}>
        <Card soft compact>
          <View style={{ flexDirection: "row", justifyContent: "space-between", gap: 10 }}>
            <View style={{ flex: 1, gap: 4 }}>
              <Text selectable style={{ color: colors.textStrong, fontSize: 18, fontWeight: "900" }}>
                Заказ #{currentOrder.id}
              </Text>
              <Muted>{currentOrder.restaurant?.name ?? "Ресторан"}</Muted>
            </View>
            <StatusChip tone={activeOrder ? "info" : "accent"}>{activeOrder ? "В работе" : courierPayout(currentOrder)}</StatusChip>
          </View>
          <View style={{ gap: 8 }}>
            <View style={{ flexDirection: "row", alignItems: "flex-start", gap: 8 }}>
              <MapPin color={colors.primary} size={iconSizes.md} strokeWidth={iconStrokeWidth} />
              <Muted>{formatAddress(currentOrder.restaurant?.address) || "Адрес ресторана появится после назначения"}</Muted>
            </View>
            {mode === "preview" ? (
              <View style={{ flexDirection: "row", alignItems: "flex-start", gap: 8 }}>
                <Navigation color={colors.secondary} size={iconSizes.md} strokeWidth={iconStrokeWidth} />
                <Muted>{formatDeliveryArea(currentOrder.delivery_address)}</Muted>
              </View>
            ) : null}
            {mode === "toClient" ? (
              <View style={{ flexDirection: "row", alignItems: "flex-start", gap: 8 }}>
                <PackageCheck color={colors.successText} size={iconSizes.md} strokeWidth={iconStrokeWidth} />
                <Muted>{formatAddress(currentOrder.delivery_address) || "Адрес клиента"}</Muted>
              </View>
            ) : null}
          </View>
          <View style={{ flexDirection: "row", flexWrap: "wrap", gap: 8 }}>
            <StatusChip tone="muted">{formatDistance(routeDistance || currentOrder.delivery_distance_meters)}</StatusChip>
            <StatusChip tone="muted">{formatDuration(routeDuration || currentOrder.delivery_duration_seconds)}</StatusChip>
            <StatusChip tone="accent">{courierPayout(currentOrder)}</StatusChip>
          </View>
        </Card>
        {mode === "preview" ? (
          <Pressable onPress={onClearSelection}>
            <Text selectable style={{ color: colors.primary, fontWeight: "900", textAlign: "center" }}>
              Смотреть другие заказы
            </Text>
          </Pressable>
        ) : null}
      </View>
    );
  };

  return (
    <Animated.View
      style={{
        position: "absolute",
        left: 0,
        right: 0,
        bottom: 0,
        height: expandedHeight,
        paddingHorizontal: 12,
        paddingTop: 8,
        paddingBottom: 12,
        borderTopLeftRadius: radii.sheet,
        borderTopRightRadius: radii.sheet,
        backgroundColor: colors.surface,
        transform: [{ translateY }],
        boxShadow: shadows.sheet,
        borderCurve: "continuous",
      }}
    >
      <View {...panResponder.panHandlers}>
        <Pressable onPress={toggleExpanded} style={{ alignItems: "center", paddingBottom: 8 }}>
          <View style={{ width: 42, height: 5, borderRadius: radii.pill, backgroundColor: colors.borderNeutral }} />
        </Pressable>

        <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", gap: 10 }}>
          <View style={{ flex: 1, gap: 3 }}>
            <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
              <ChevronUp color={colors.textMuted} size={iconSizes.md} strokeWidth={iconStrokeWidth} />
              <Text selectable style={{ fontSize: 19, fontWeight: "900", color: colors.textStrong }}>
                {titleForMode(open, mode)}
              </Text>
            </View>
            <Muted>{subtitleForMode(open, mode, currentOrder, availableOrders.length, openedAt)}</Muted>
          </View>
          <StatusChip tone={open ? "success" : "muted"}>{open ? "Актив" : "Offline"}</StatusChip>
        </View>
      </View>

      <View style={{ height: 12 }} />

      {profileError ? <Text selectable style={{ color: colors.dangerText }}>Профиль курьера не найден или отключен.</Text> : null}

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ gap: 12, paddingBottom: 12 }}>
        {profile ? (
          <View style={{ gap: 8 }}>
            <ProfileRow Icon={Clock} label="Смена открыта" value={formatShiftTime(openedAt)} />
            <ProfileRow Icon={CircleCheck} label="Статус" value={profile.status === "ACTIVE" ? "Актив" : profile.status} />
            <ProfileRow Icon={Bike} label="Тип доставки" value={vehicleLabel(profile.vehicle)} />
            <ProfileRow Icon={Star} label="Рейтинг" value={profile.rating ?? "нет"} />
            {earnings ? <ProfileRow Icon={Wallet} label="Сегодня" value={`${earnings.today.earnings_sum} ₽`} /> : null}
          </View>
        ) : null}

        {renderDetails()}

        <ActionButton
          mode={mode}
          order={currentOrder}
          disabled={busy || profileError}
          onAssign={onAssign}
          onPickedUp={onPickedUp}
          onDelivered={onDelivered}
        />

        <View style={{ flexDirection: "row", gap: 8 }}>
          <View style={{ flex: 1 }}>
            <Button disabled={profileError || busy} variant={open ? "ghost" : "primary"} onPress={onToggleShift}>
              {open ? "Завершить смену" : "Начать смену"}
            </Button>
          </View>
          <Pressable
            onPress={onLogout}
            style={{
              width: 52,
              minHeight: 48,
              alignItems: "center",
              justifyContent: "center",
              borderRadius: radii.pill,
              backgroundColor: colors.surfaceSoft,
              borderWidth: 1,
              borderColor: colors.borderNeutral,
            }}
          >
            <LogOut color={colors.textSubtle} size={iconSizes.lg} strokeWidth={iconStrokeWidth} />
          </Pressable>
        </View>
      </ScrollView>
    </Animated.View>
  );
}

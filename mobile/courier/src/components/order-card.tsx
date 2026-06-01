import { Link } from "expo-router";
import { Pressable, Text, View } from "react-native";
import { MapPin, Navigation, Wallet } from "lucide-react-native";
import { Button, Card, Muted } from "@/components/ui";
import type { CourierOrder } from "@/domain/courier/types";
import { courierPayout, formatAddress, formatDeliveryArea, formatDistance, formatDuration, orderStatusLabel } from "@/domain/courier/presentation";
import { colors, iconSizes, iconStrokeWidth, radii } from "@/theme/tokens";

export function OrderCard({
  order,
  actionLabel,
  onAction,
  onPress,
  active = false,
}: {
  order: CourierOrder;
  actionLabel?: string;
  onAction?: () => void;
  onPress?: () => void;
  active?: boolean;
}) {
  const content = (
    <View style={{ gap: 8 }}>
      <View style={{ flexDirection: "row", justifyContent: "space-between", alignItems: "flex-start", gap: 8 }}>
        <View style={{ flex: 1, gap: 2 }}>
          <Text selectable style={{ color: colors.textStrong, fontSize: 16, fontWeight: "900" }}>
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
      <Muted>Статус: {orderStatusLabel(order.status)}</Muted>
      <View style={{ gap: 5 }}>
        {!!formatAddress(order.restaurant?.address) && (
          <View style={{ flexDirection: "row", alignItems: "flex-start", gap: 6 }}>
            <MapPin color={colors.primary} size={iconSizes.sm} strokeWidth={iconStrokeWidth} />
            <Muted>{formatAddress(order.restaurant?.address)}</Muted>
          </View>
        )}
        <View style={{ flexDirection: "row", alignItems: "flex-start", gap: 6 }}>
          <Navigation color={active ? colors.successText : colors.secondary} size={iconSizes.sm} strokeWidth={iconStrokeWidth} />
          <Muted>{active ? formatAddress(order.delivery_address) || "Адрес клиента" : formatDeliveryArea(order.delivery_address)}</Muted>
        </View>
      </View>
      <View style={{ flexDirection: "row", flexWrap: "wrap", gap: 8 }}>
        <Muted>{formatDistance(order.courier_approach_distance_meters ?? order.delivery_distance_meters)}</Muted>
        <Muted>{formatDuration(order.delivery_duration_seconds)}</Muted>
      </View>
    </View>
  );

  return (
    <Card soft={active}>
      {onPress ? (
        <Pressable onPress={onPress}>{content}</Pressable>
      ) : (
        <Link href={`/orders/${order.id}`} asChild>
          <Pressable>{content}</Pressable>
        </Link>
      )}
      {actionLabel && onAction ? <Button onPress={onAction}>{actionLabel}</Button> : null}
    </Card>
  );
}

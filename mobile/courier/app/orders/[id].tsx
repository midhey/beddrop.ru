import { useLocalSearchParams } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Text } from "react-native";
import { courierApi } from "@/api/courier";
import { RouteMap } from "@/components/route-map";
import { Screen } from "@/components/screen";
import { Card, Muted, Title } from "@/components/ui";
import { courierPayout, formatAddress, orderStatusLabel } from "@/domain/courier/presentation";

export default function OrderDetailScreen() {
  const params = useLocalSearchParams<{ id: string }>();
  const orderId = Number(params.id);
  const active = useQuery({ queryKey: ["courier", "orders", "active"], queryFn: courierApi.active, retry: false });
  const history = useQuery({ queryKey: ["courier", "orders", "history"], queryFn: courierApi.history, retry: false });
  const order = [...(active.data ?? []), ...(history.data ?? [])].find((item) => item.id === orderId);

  if (!order) {
    return (
      <Screen>
        <Text selectable>Заказ не найден в активных заказах или истории.</Text>
      </Screen>
    );
  }

  return (
    <Screen>
      <Card>
        <Title>Заказ #{order.id}</Title>
        <Muted>Статус: {orderStatusLabel(order.status)}</Muted>
        <Muted>Курьеру: {courierPayout(order)}</Muted>
        <Muted>Ресторан: {order.restaurant?.name ?? "Ресторан"}</Muted>
        {!!formatAddress(order.restaurant?.address) && <Muted>Откуда: {formatAddress(order.restaurant?.address)}</Muted>}
        {!!formatAddress(order.delivery_address) && <Muted>Куда: {formatAddress(order.delivery_address)}</Muted>}
      </Card>
      <RouteMap order={order} />
    </Screen>
  );
}

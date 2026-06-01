import { useQuery } from "@tanstack/react-query";
import { Text } from "react-native";
import { courierApi } from "@/api/courier";
import { OrderCard } from "@/components/order-card";
import { Screen } from "@/components/screen";
import { Title } from "@/components/ui";

export default function HistoryScreen() {
  const history = useQuery({ queryKey: ["courier", "orders", "history"], queryFn: courierApi.history, retry: false });

  return (
    <Screen>
      <Title>История заказов</Title>
      {history.data?.length ? history.data.map((order) => (
        <OrderCard key={order.id} order={order} />
      )) : <Text selectable>История пока пуста.</Text>}
    </Screen>
  );
}

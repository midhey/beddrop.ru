import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Pressable, ScrollView, Text, View } from "react-native";
import { CalendarDays, CircleAlert, PackageCheck, TrendingUp, Wallet } from "lucide-react-native";
import { courierApi } from "@/api/courier";
import { Screen } from "@/components/screen";
import { Button, Card, Muted, StatusChip, Title } from "@/components/ui";
import { formatPrice } from "@/domain/courier/presentation";
import type { CourierEarningsBucket, CourierOrder } from "@/domain/courier/types";
import { colors, iconSizes, iconStrokeWidth, radii } from "@/theme/tokens";

const emptyBucket: CourierEarningsBucket = {
  deliveries_count: 0,
  earnings_sum: "0.00",
  total_orders_sum: "0.00",
};

const monthLabel = new Intl.DateTimeFormat("ru-RU", { month: "long" });

const orderCompletedAt = (order: CourierOrder) => new Date(order.updated_at ?? order.created_at);

const buildMonthSeries = (orders: CourierOrder[]) => {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const counts = Array.from({ length: daysInMonth }, (_, index) => ({
    day: index + 1,
    count: 0,
  }));

  orders
    .filter((order) => order.status === "DELIVERED")
    .forEach((order) => {
      const date = orderCompletedAt(order);
      if (date.getFullYear() !== year || date.getMonth() !== month) return;
      counts[date.getDate() - 1].count += 1;
    });

  return counts;
};

const EarningsRow = ({
  title,
  bucket,
}: {
  title: string;
  bucket: CourierEarningsBucket;
}) => (
  <View
    style={{
      flexDirection: "row",
      alignItems: "center",
      gap: 12,
      paddingVertical: 12,
      borderBottomWidth: 1,
      borderBottomColor: colors.borderLight,
    }}
  >
    <View
      style={{
        width: 38,
        height: 38,
        alignItems: "center",
        justifyContent: "center",
        borderRadius: 19,
        backgroundColor: colors.bgAlt,
      }}
    >
      <Wallet color={colors.primary} size={iconSizes.lg} strokeWidth={iconStrokeWidth} />
    </View>
    <View style={{ flex: 1, gap: 2 }}>
      <Text selectable style={{ color: colors.textStrong, fontSize: 15, fontWeight: "900" }}>
        {title}
      </Text>
      <Muted>
        {bucket.deliveries_count} доставок · оборот {formatPrice(bucket.total_orders_sum)}
      </Muted>
    </View>
    <Text selectable style={{ color: colors.textStrong, fontSize: 17, fontWeight: "900", fontVariant: ["tabular-nums"] }}>
      {formatPrice(bucket.earnings_sum)}
    </Text>
  </View>
);

const MonthOrdersChart = ({ orders }: { orders: CourierOrder[] }) => {
  const series = useMemo(() => buildMonthSeries(orders), [orders]);
  const max = Math.max(1, ...series.map((item) => item.count));
  const total = series.reduce((sum, item) => sum + item.count, 0);
  const activeDays = series.filter((item) => item.count > 0).length;

  return (
    <Card>
      <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", gap: 10 }}>
        <View style={{ flex: 1, gap: 3 }}>
          <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
            <CalendarDays color={colors.primary} size={iconSizes.lg} strokeWidth={iconStrokeWidth} />
            <Title size="sm">Заказы за {monthLabel.format(new Date())}</Title>
          </View>
          <Muted>Доставленные заказы по дням месяца</Muted>
        </View>
        <StatusChip tone="accent">{total}</StatusChip>
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8, paddingVertical: 6 }}>
        {series.map((item) => {
          const height = 18 + Math.round((item.count / max) * 86);
          const filled = item.count > 0;

          return (
            <View key={item.day} style={{ width: 28, alignItems: "center", justifyContent: "flex-end", gap: 6 }}>
              <Text selectable style={{ color: filled ? colors.textStrong : colors.textSoft, fontSize: 11, fontWeight: "800", fontVariant: ["tabular-nums"] }}>
                {item.count || ""}
              </Text>
              <View
                style={{
                  width: 16,
                  height,
                  borderRadius: radii.pill,
                  backgroundColor: filled ? colors.primary : colors.borderLight,
                  opacity: filled ? 1 : 0.7,
                }}
              />
              <Text selectable style={{ color: colors.textMuted, fontSize: 10, fontWeight: "800", fontVariant: ["tabular-nums"] }}>
                {item.day}
              </Text>
            </View>
          );
        })}
      </ScrollView>

      <View style={{ flexDirection: "row", gap: 8, flexWrap: "wrap" }}>
        <StatusChip tone="muted">{activeDays} активных дней</StatusChip>
        <StatusChip tone="muted">{total} доставок</StatusChip>
      </View>
    </Card>
  );
};

export default function EarningsScreen() {
  const [showWithdrawNotice, setShowWithdrawNotice] = useState(false);
  const earnings = useQuery({ queryKey: ["courier", "earnings"], queryFn: courierApi.earnings, retry: false });
  const history = useQuery({ queryKey: ["courier", "orders", "history"], queryFn: courierApi.history, retry: false });
  const data = earnings.data;
  const rows = [
    { title: "Сегодня", bucket: data?.today ?? emptyBucket },
    { title: "Неделя", bucket: data?.week ?? emptyBucket },
    { title: "Все время", bucket: data?.total ?? emptyBucket },
  ];

  return (
    <Screen>
      <View style={{ gap: 4 }}>
        <Title>Кошелек</Title>
        <Muted>Заработок, доставленные заказы и будущий вывод денег.</Muted>
      </View>

      <Card>
        <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
          <TrendingUp color={colors.primary} size={iconSizes.lg} strokeWidth={iconStrokeWidth} />
          <Title size="sm">Заработок</Title>
        </View>
        <View>
          {rows.map((row) => (
            <EarningsRow key={row.title} title={row.title} bucket={row.bucket} />
          ))}
        </View>
      </Card>

      <MonthOrdersChart orders={history.data ?? []} />

      <Button onPress={() => setShowWithdrawNotice(true)}>Вывести деньги</Button>

      {showWithdrawNotice ? (
        <View
          style={{
            flexDirection: "row",
            alignItems: "flex-start",
            gap: 10,
            padding: 14,
            borderRadius: radii.lg,
            backgroundColor: colors.infoBg,
            borderWidth: 1,
            borderColor: colors.borderLight,
          }}
        >
          <View
            style={{
              width: 34,
              height: 34,
              alignItems: "center",
              justifyContent: "center",
              borderRadius: 17,
              backgroundColor: colors.surface,
            }}
          >
            <CircleAlert color={colors.infoText} size={iconSizes.lg} strokeWidth={iconStrokeWidth} />
          </View>
          <View style={{ flex: 1, gap: 3 }}>
            <Text selectable style={{ color: colors.textStrong, fontSize: 15, fontWeight: "900" }}>
              Вывод пока не работает
            </Text>
            <Muted>Кнопка уже на месте, подключение выплат будет отдельным шагом.</Muted>
          </View>
          <Pressable onPress={() => setShowWithdrawNotice(false)} hitSlop={10}>
            <Text selectable style={{ color: colors.infoText, fontWeight: "900" }}>
              OK
            </Text>
          </Pressable>
        </View>
      ) : null}

      {history.isError ? (
        <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
          <PackageCheck color={colors.textMuted} size={iconSizes.md} strokeWidth={iconStrokeWidth} />
          <Muted>Историю заказов не удалось загрузить, диаграмма временно пустая.</Muted>
        </View>
      ) : null}
    </Screen>
  );
}

import { Tabs } from "expo-router";
import { Clock, History, ListChecks, Wallet } from "lucide-react-native";
import { colors, iconStrokeWidth } from "@/theme/tokens";

export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarStyle: { borderTopColor: colors.borderNeutral, backgroundColor: colors.surface },
        headerTintColor: colors.textStrong,
      }}
    >
      <Tabs.Screen
        name="shift"
        options={{ title: "Смена", headerShown: false, tabBarIcon: ({ color, size }) => <Clock color={color} size={size} strokeWidth={iconStrokeWidth} /> }}
      />
      <Tabs.Screen
        name="orders"
        options={{ title: "Заказы", tabBarIcon: ({ color, size }) => <ListChecks color={color} size={size} strokeWidth={iconStrokeWidth} /> }}
      />
      <Tabs.Screen
        name="history"
        options={{ title: "История", tabBarIcon: ({ color, size }) => <History color={color} size={size} strokeWidth={iconStrokeWidth} /> }}
      />
      <Tabs.Screen
        name="earnings"
        options={{ title: "Кошелек", tabBarIcon: ({ color, size }) => <Wallet color={color} size={size} strokeWidth={iconStrokeWidth} /> }}
      />
    </Tabs>
  );
}

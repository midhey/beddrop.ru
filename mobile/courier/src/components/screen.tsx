import type { PropsWithChildren } from "react";
import { ScrollView } from "react-native";
import { colors } from "@/theme/tokens";

export function Screen({ children }: PropsWithChildren) {
  return (
    <ScrollView
      contentInsetAdjustmentBehavior="automatic"
      style={{ backgroundColor: colors.bgTint }}
      contentContainerStyle={{ padding: 16, gap: 12 }}
    >
      {children}
    </ScrollView>
  );
}

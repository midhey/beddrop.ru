import { useState, type PropsWithChildren } from "react";
import { Pressable, Text, TextInput, type TextInputProps, View } from "react-native";
import { colors, radii, shadows } from "@/theme/tokens";

export function Card({
  children,
  soft = false,
  compact = false,
}: PropsWithChildren<{ soft?: boolean; compact?: boolean }>) {
  return (
    <View
      style={{
        padding: compact ? 12 : 14,
        gap: compact ? 8 : 10,
        borderRadius: radii.lg,
        backgroundColor: soft ? colors.surfaceSoft : colors.surface,
        borderWidth: 1,
        borderColor: soft ? colors.borderLight : colors.borderNeutral,
        boxShadow: shadows.card,
        borderCurve: "continuous",
      }}
    >
      {children}
    </View>
  );
}

export function Title({ children, size = "md" }: PropsWithChildren<{ size?: "sm" | "md" | "lg" }>) {
  const fontSize = size === "lg" ? 22 : size === "sm" ? 16 : 20;

  return <Text selectable style={{ fontSize, fontWeight: "900", color: colors.textStrong }}>{children}</Text>;
}

export function Muted({ children }: PropsWithChildren) {
  return <Text selectable style={{ fontSize: 13, lineHeight: 18, color: colors.textMuted }}>{children}</Text>;
}

export function Field(props: TextInputProps) {
  return (
    <TextInput
      placeholderTextColor={colors.textSoft}
      style={{
        minHeight: 48,
        borderWidth: 1,
        borderColor: colors.borderNeutral,
        borderRadius: radii.md,
        padding: 12,
        fontSize: 16,
        color: colors.textStrong,
        backgroundColor: colors.surface,
      }}
      {...props}
    />
  );
}

export function FloatingField({
  label,
  value,
  onFocus,
  onBlur,
  ...props
}: TextInputProps & { label: string }) {
  const [focused, setFocused] = useState(false);
  const active = focused || !!value;

  return (
    <View>
      <View
        style={{
          minHeight: 60,
          justifyContent: "center",
          borderWidth: focused ? 1.5 : 1,
          borderColor: focused ? colors.primary : "rgba(122, 59, 255, 0.18)",
          borderRadius: radii.lg,
          backgroundColor: "rgba(255, 255, 255, 0.58)",
        }}
      >
        <Text
          pointerEvents="none"
          selectable={false}
          style={{
            position: "absolute",
            left: 14,
            top: active ? 8 : 19,
            color: focused ? colors.primary : colors.textMuted,
            fontSize: active ? 12 : 16,
            lineHeight: active ? 15 : 20,
            fontWeight: active ? "900" : "700",
          }}
        >
          {label}
        </Text>
        <TextInput
          value={value}
          placeholder=""
          placeholderTextColor={colors.textSoft}
          onFocus={(event) => {
            setFocused(true);
            onFocus?.(event);
          }}
          onBlur={(event) => {
            setFocused(false);
            onBlur?.(event);
          }}
          style={{
            minHeight: 58,
            paddingHorizontal: 14,
            paddingTop: active ? 18 : 0,
            color: colors.textStrong,
            fontSize: 16,
            fontWeight: "700",
          }}
          {...props}
        />
      </View>
    </View>
  );
}

export function Button({
  children,
  disabled,
  onPress,
  variant = "primary",
}: PropsWithChildren<{ disabled?: boolean; onPress?: () => void; variant?: "primary" | "secondary" | "ghost" | "danger" }>) {
  const isGhost = variant === "ghost";
  const backgroundColor = disabled
    ? colors.textSoft
    : variant === "secondary"
      ? colors.secondary
      : variant === "danger"
        ? colors.dangerText
        : isGhost
          ? colors.surfaceSoft
          : colors.primary;
  const textColor = variant === "secondary" || isGhost ? colors.textStrong : colors.surface;

  return (
    <Pressable
      disabled={disabled}
      onPress={onPress}
      style={{
        alignItems: "center",
        justifyContent: "center",
        minHeight: 48,
        paddingVertical: 12,
        paddingHorizontal: 16,
        borderRadius: radii.pill,
        backgroundColor,
        borderWidth: isGhost ? 1 : 0,
        borderColor: colors.borderNeutral,
        opacity: disabled ? 0.72 : 1,
      }}
    >
      <Text style={{ color: textColor, fontWeight: "900" }}>{children}</Text>
    </Pressable>
  );
}

export function StatusChip({
  children,
  tone = "muted",
}: PropsWithChildren<{ tone?: "muted" | "success" | "danger" | "info" | "accent" }>) {
  const toneMap = {
    muted: { bg: colors.mutedBg, text: colors.mutedText },
    success: { bg: colors.successBg, text: colors.successText },
    danger: { bg: colors.dangerBg, text: colors.dangerText },
    info: { bg: colors.infoBg, text: colors.infoText },
    accent: { bg: colors.bgAlt, text: colors.primary },
  }[tone];

  return (
    <View style={{ alignSelf: "flex-start", paddingVertical: 6, paddingHorizontal: 10, borderRadius: radii.pill, backgroundColor: toneMap.bg }}>
      <Text selectable style={{ color: toneMap.text, fontSize: 12, fontWeight: "900" }}>
        {children}
      </Text>
    </View>
  );
}

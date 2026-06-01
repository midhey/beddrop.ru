import type { PropsWithChildren } from "react";
import { ImageBackground, KeyboardAvoidingView, ScrollView, Text, useWindowDimensions, View } from "react-native";
import { colors, radii, shadows } from "@/theme/tokens";
import authHero from "../../assets/images/auth-hero.webp";
import authHeroVert from "../../assets/images/auth-hero-vert.webp";

export function AuthScreen({
  children,
  title,
  compact = false,
}: PropsWithChildren<{ title: string; subtitle?: string; compact?: boolean }>) {
  const { width, height } = useWindowDimensions();
  const background = height >= width ? authHeroVert : authHero;

  return (
    <ImageBackground source={background} resizeMode="cover" style={{ flex: 1, backgroundColor: colors.mapDark }}>
      <KeyboardAvoidingView behavior={process.env.EXPO_OS === "ios" ? "padding" : undefined} style={{ flex: 1 }}>
        <View style={{ flex: 1, justifyContent: "flex-end" }}>
          <ScrollView
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
            contentContainerStyle={{
              flexGrow: 1,
              justifyContent: "flex-end",
              paddingTop: 96,
            }}
          >
            <View
              style={{
                gap: 16,
                paddingHorizontal: 18,
                paddingTop: 22,
                paddingBottom: compact ? 20 : 28,
                marginHorizontal: 10,
                marginBottom: 10,
                borderRadius: radii.sheet,
                backgroundColor: "rgba(244, 242, 255, 0.96)",
                borderWidth: 1,
                borderColor: "rgba(122, 59, 255, 0.16)",
                boxShadow: shadows.card,
                borderCurve: "continuous",
              }}
            >
              <View style={{ gap: 2 }}>
                <Text
                  selectable
                  style={{
                    color: colors.primary,
                    fontFamily: process.env.EXPO_OS === "android" ? "sans-serif-condensed" : "Georgia",
                    fontSize: 29,
                    fontWeight: "900",
                    letterSpacing: 0.2,
                  }}
                >
                  {title}
                </Text>
              </View>
              {children}
            </View>
          </ScrollView>
        </View>
      </KeyboardAvoidingView>
    </ImageBackground>
  );
}

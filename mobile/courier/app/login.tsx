import { Link, router } from "expo-router";
import { useState } from "react";
import { Text, View } from "react-native";
import { AuthScreen } from "@/components/auth-screen";
import { Button, FloatingField, Muted } from "@/components/ui";
import { useAuth } from "@/store/auth";
import { colors } from "@/theme/tokens";

export default function LoginScreen() {
  const login = useAuth((state) => state.login);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    setError("");
    try {
      await login(email, password);
      router.replace("/(tabs)/shift");
    } catch (e: any) {
      setError(e?.response?.data?.message ?? "Не удалось войти");
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthScreen title="Вход курьера" compact>
      <View style={{ gap: 10 }}>
        <FloatingField value={email} onChangeText={setEmail} label="Email" keyboardType="email-address" autoCapitalize="none" />
        <FloatingField value={password} onChangeText={setPassword} label="Пароль" secureTextEntry />
        {!!error && <Text selectable style={{ color: colors.dangerText }}>{error}</Text>}
      </View>
      <Button disabled={loading} onPress={submit}>{loading ? "Входим..." : "Войти"}</Button>
      <Link href="/register" style={{ alignSelf: "center" }}>
        <Muted>Создать аккаунт</Muted>
      </Link>
    </AuthScreen>
  );
}

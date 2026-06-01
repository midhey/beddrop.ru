import { Link, router } from "expo-router";
import { useState } from "react";
import { Text, View } from "react-native";
import { AuthScreen } from "@/components/auth-screen";
import { Button, FloatingField, Muted } from "@/components/ui";
import { useAuth } from "@/store/auth";
import { colors } from "@/theme/tokens";

export default function RegisterScreen() {
  const register = useAuth((state) => state.register);
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    setError("");
    try {
      await register({
        name: name || null,
        email,
        phone,
        password,
        password_confirmation: passwordConfirmation,
      });
      router.replace("/(tabs)/shift");
    } catch (e: any) {
      setError(e?.response?.data?.message ?? "Не удалось зарегистрироваться");
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthScreen title="Стать курьером">
      <View style={{ gap: 10 }}>
        <FloatingField value={name} onChangeText={setName} label="Имя" />
        <FloatingField value={email} onChangeText={setEmail} label="Email" keyboardType="email-address" autoCapitalize="none" />
        <FloatingField value={phone} onChangeText={setPhone} label="Телефон" keyboardType="phone-pad" />
        <FloatingField value={password} onChangeText={setPassword} label="Пароль" secureTextEntry />
        <FloatingField value={passwordConfirmation} onChangeText={setPasswordConfirmation} label="Повторите пароль" secureTextEntry />
        {!!error && <Text selectable style={{ color: colors.dangerText }}>{error}</Text>}
      </View>
      <Button disabled={loading} onPress={submit}>{loading ? "Создаем..." : "Создать аккаунт"}</Button>
      <Link href="/login" style={{ alignSelf: "center" }}>
        <Muted>Уже есть аккаунт</Muted>
      </Link>
    </AuthScreen>
  );
}

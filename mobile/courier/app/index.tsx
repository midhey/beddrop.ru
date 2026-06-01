import { Redirect } from "expo-router";
import { ActivityIndicator, View } from "react-native";
import { useBootstrapAuth } from "@/hooks/use-bootstrap-auth";
import { useAuth } from "@/store/auth";

export default function IndexScreen() {
  useBootstrapAuth();
  const ready = useAuth((state) => state.ready);
  const user = useAuth((state) => state.user);

  if (!ready) {
    return (
      <View style={{ flex: 1, alignItems: "center", justifyContent: "center" }}>
        <ActivityIndicator />
      </View>
    );
  }

  return <Redirect href={user ? "/(tabs)/shift" : "/login"} />;
}

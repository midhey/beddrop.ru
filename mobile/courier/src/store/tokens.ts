import * as SecureStore from "expo-secure-store";

const ACCESS = "beddrop.access_token";
const REFRESH = "beddrop.refresh_token";

export const tokenStore = {
  getAccess: () => SecureStore.getItemAsync(ACCESS),
  getRefresh: () => SecureStore.getItemAsync(REFRESH),
  async set(access: string, refresh?: string | null) {
    await SecureStore.setItemAsync(ACCESS, access);
    if (refresh) {
      await SecureStore.setItemAsync(REFRESH, refresh);
    }
  },
  async clear() {
    await SecureStore.deleteItemAsync(ACCESS);
    await SecureStore.deleteItemAsync(REFRESH);
  },
};

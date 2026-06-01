import axios, { AxiosError, type InternalAxiosRequestConfig } from "axios";
import Constants from "expo-constants";
import { tokenStore } from "@/store/tokens";

const extra = Constants.expoConfig?.extra as { apiBase?: string } | undefined;
export const apiBase = extra?.apiBase;

if (!apiBase) {
  throw new Error("Courier API base URL is not configured. Set EXPO_PUBLIC_API_BASE.");
}

export const api = axios.create({ baseURL: apiBase });

const noRefresh = (url?: string) =>
  url === "/auth/refresh" || url === "/auth/login" || url === "/auth/register";

api.interceptors.request.use(async (config) => {
  const token = await tokenStore.getAccess();
  if (token) {
    config.headers = config.headers ?? {};
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

let refreshPromise: Promise<string | null> | null = null;

const refreshAccessToken = async (): Promise<string | null> => {
  const refreshToken = await tokenStore.getRefresh();
  if (!refreshToken) return null;

  try {
    const { data } = await axios.post(`${apiBase}/auth/refresh`, {
      refresh_token: refreshToken,
      client_type: "mobile",
    });
    await tokenStore.set(data.access_token, data.refresh_token);
    return data.access_token as string;
  } catch {
    await tokenStore.clear();
    return null;
  }
};

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const original = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

    if (error.response?.status === 401 && original && !original._retry && !noRefresh(original.url)) {
      original._retry = true;
      refreshPromise = refreshPromise ?? refreshAccessToken();
      const token = await refreshPromise;
      refreshPromise = null;

      if (token) {
        original.headers = original.headers ?? {};
        original.headers.Authorization = `Bearer ${token}`;
        return api(original);
      }
    }

    return Promise.reject(error);
  },
);

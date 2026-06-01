import { create } from "zustand";
import { api } from "@/api/client";
import { tokenStore } from "@/store/tokens";

export interface User {
  id: number;
  email: string;
  phone: string;
  name: string | null;
  is_admin: boolean;
  is_banned: boolean;
}

interface RegisterPayload {
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
  name: string | null;
}

interface AuthState {
  user: User | null;
  ready: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (payload: RegisterPayload) => Promise<void>;
  logout: () => Promise<void>;
  bootstrap: () => Promise<void>;
}

export const useAuth = create<AuthState>((set) => ({
  user: null,
  ready: false,

  async login(email, password) {
    const { data } = await api.post("/auth/login", { email, password, client_type: "mobile" });
    await tokenStore.set(data.access_token, data.refresh_token);
    set({ user: data.user, ready: true });
  },

  async register(payload) {
    const { data } = await api.post("/auth/register", { ...payload, client_type: "mobile" });
    await tokenStore.set(data.access_token, data.refresh_token);
    set({ user: data.user, ready: true });
  },

  async logout() {
    try {
      await api.post("/auth/logout");
    } catch {
    }
    await tokenStore.clear();
    set({ user: null, ready: true });
  },

  async bootstrap() {
    const token = await tokenStore.getAccess();
    if (!token) {
      set({ user: null, ready: true });
      return;
    }

    try {
      const { data } = await api.get("/profile/me");
      set({ user: data.user, ready: true });
    } catch {
      await tokenStore.clear();
      set({ user: null, ready: true });
    }
  },
}));

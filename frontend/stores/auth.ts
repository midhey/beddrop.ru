import {defineStore} from "pinia";

interface User {
    id: number;
    email: string;
    phone: string;
    name: string | null;
    is_admin: boolean;
    is_banned: boolean;
}

interface AuthState {
    user: User | null;
    accessToken: string | null;
    loading: boolean;
    error: string | null;
}

interface RegisterPayload {
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
    name: string | null;
}

interface LoginPayload {
    email: string;
    password: string;
}

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        user: null,
        accessToken: null,
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state): boolean => !!state.accessToken,
        isAdmin: (state): boolean => !!state.user?.is_admin,
    },

    persist: {
        storage: piniaPluginPersistedstate.localStorage(),
        pick: ['accessToken'],
    },

    actions: {
        setAuth(user: User, token: string) {
            this.user = user;
            this.accessToken = token;
        },

        clearAuth() {
            this.user = null;
            this.accessToken = null;
        },

        setError(message: string | null) {
            this.error = message;
        },

        clearError() {
            this.error = null;
        },

        async register(payload: RegisterPayload) {
            this.loading = true;
            this.clearError();

            try {
                const { $api, $notify } = useNuxtApp();
                const { data } = await $api.post('/auth/register', payload);
                this.setAuth(data.user, data.access_token);
                $notify?.success?.('Вы успешно зарегистрированы');
                return data;
            } catch (error: any) {
                this.handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async login(payload: LoginPayload) {
            this.loading = true;
            this.clearError();

            try {
                const { $api, $notify } = useNuxtApp();
                const { data } = await $api.post('/auth/login', payload);
                this.setAuth(data.user, data.access_token);
                $notify?.success?.('Вы успешно вошли');
                return data;
            } catch (error: any) {
                this.handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async profile(silent = false) {
            if (!this.accessToken) return null;

            this.loading = true;
            if (!silent) {
                this.clearError();
            }

            try {
                const { $api } = useNuxtApp();
                const { data } = await $api.get('/auth/me');
                this.user = data.user;
                return data.user as User;
            } catch (error: any) {
                if (!silent) {
                    this.handleError(error);
                }
                this.clearAuth();
                if (!silent) {
                    throw error;
                }
                return null;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            if (!this.accessToken) return null;
            this.loading = true;
            this.clearError();

            try {
                const { $api, $notify } = useNuxtApp();
                await $api.post('/auth/logout');
                $notify?.info?.('Вы вышли из аккаунта');
                return true;
            } catch (error: any) {
                this.handleError(error);
                throw error;
            } finally {
                this.clearAuth();
                this.loading = false;
            }
        },

        async refresh() {
            if (!this.accessToken) return null;

            try {
                const { $api, $notify } = useNuxtApp();
                const { data } = await $api.post('/auth/refresh');
                this.accessToken = data.access_token;
                return true;
            } catch (error: any) {
                $notify?.info?.('Ваша сессия истекла. Повторите авторизацию');
                this.clearAuth();
                throw error;
            }
        },

        handleError(error: any) {
            const { $notify } = useNuxtApp();

            let message = 'Произошла неизвестная ошибка. Обратитесь в поддержку сервиса';

            if (error?.response?.data?.message) message = error.response.data.message;
            else if (error?.response?.data?.errors) {
                const errors = error.response.data.errors;
                const firstField = Object.keys(errors)[0];
                if (firstField && Array.isArray(errors[firstField]) && errors[firstField].length) {
                    message = errors[firstField][0];
                }
            }
            else if (error?.message) {
                message = error.message;
            }

            this.error = message;
            $notify?.failure?.(message);
            console.error('[AuthStore] error:', message, error);
        }
    }
})
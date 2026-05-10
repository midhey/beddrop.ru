import {defineStore} from "pinia";
import { useFeedback } from '~/composables/useFeedback';

const getErrorStatus = (error: any): number | null => {
    const status = error?.response?.status;

    return typeof status === 'number' ? status : null;
};

const isAuthFailureStatus = (status: number | null): boolean => {
    return status === 401 || status === 403;
};

let sessionPromise: Promise<boolean> | null = null;
let refreshPromise: Promise<boolean> | null = null;

export interface User {
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
    initialized: boolean;
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

interface UpdateProfilePayload {
    name?: string | null;
    email?: string;
    phone?: string;
}

interface ChangePasswordPayload {
    current_password: string;
    password: string;
    password_confirmation: string;
}

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        user: null,
        accessToken: null,
        loading: false,
        error: null,
        initialized: false,
    }),

    getters: {
        isAuthenticated: (state): boolean => !!state.accessToken,
        isAdmin: (state): boolean => !!state.user?.is_admin,
        isReady: (state): boolean => state.initialized,
    },

    actions: {
        setAuth(user: User, token: string) {
            this.user = user;
            this.accessToken = token;
            this.initialized = true;
        },

        setUser(user: User | null) {
            this.user = user;
        },

        clearAuth() {
            this.user = null;
            this.accessToken = null;
            this.initialized = true;
        },

        setInitialized(value: boolean) {
            this.initialized = value;
        },

        setError(message: string | null) {
            this.error = message;
        },

        clearError() {
            this.error = null;
        },

        // Авторизация

        async register(payload: RegisterPayload) {
            this.loading = true;
            this.clearError();

            try {
                const { $api } = useNuxtApp();
                const feedback = useFeedback();
                const { data } = await $api.post('/auth/register', {
                    ...payload,
                    client_type: 'web',
                });
                this.setAuth(data.user, data.access_token);
                feedback.success('Вы успешно зарегистрированы');
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
                const { $api } = useNuxtApp();
                const feedback = useFeedback();
                const { data } = await $api.post('/auth/login', {
                    ...payload,
                    client_type: 'web',
                });
                this.setAuth(data.user, data.access_token);
                feedback.success('Вы успешно вошли');
                return data;
            } catch (error: any) {
                this.handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            this.loading = true;
            this.clearError();

            try {
                const { $api } = useNuxtApp();
                const feedback = useFeedback();
                await $api.post('/auth/logout');
                this.clearAuth();
                feedback.info('Вы вышли из аккаунта');
                return true;
            } catch (error: any) {
                const status = getErrorStatus(error);

                if (isAuthFailureStatus(status)) {
                    this.clearAuth();
                    return true;
                }

                this.handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async ensureSession(force = false) {
            if (this.initialized && !force) {
                return this.isAuthenticated;
            }

            if (sessionPromise && !force) {
                return sessionPromise;
            }

            this.initialized = false;

            sessionPromise = (async () => {
                if (this.accessToken) {
                    this.initialized = true;
                    return true;
                }

                try {
                    await this.refresh(true);
                    return !!this.accessToken;
                } catch {
                    return false;
                } finally {
                    this.initialized = true;
                    sessionPromise = null;
                }
            })();

            return sessionPromise;
        },

        async refresh(silent = false) {
            if (refreshPromise) {
                return refreshPromise;
            }

            refreshPromise = (async () => {
                try {
                    const { $api } = useNuxtApp();
                    const { data } = await $api.post('/auth/refresh');
                    this.accessToken = data.access_token;
                    this.initialized = true;
                    return true;
                } catch (error: any) {
                    const feedback = useFeedback();
                    const status = getErrorStatus(error);

                    if (isAuthFailureStatus(status)) {
                        if (!silent) {
                            feedback.info('Ваша сессия истекла. Повторите авторизацию');
                        }

                        this.clearAuth();
                    }

                    throw error;
                } finally {
                    refreshPromise = null;
                }
            })();

            return refreshPromise;
        },

        // Работа с профилем

        async profile(silent = false) {
            if (!this.accessToken) {
                const hasSession = await this.ensureSession();

                if (!hasSession || !this.accessToken) {
                    return null;
                }
            }

            if (!this.accessToken) return null;

            this.loading = true;
            if (!silent) {
                this.clearError();
            }

            try {
                const { $api } = useNuxtApp();
                const { data } = await $api.get('/profile/me');
                this.user = data.user;
                return data.user as User;
            } catch (error: any) {
                const status = getErrorStatus(error);

                if (isAuthFailureStatus(status)) {
                    this.clearAuth();
                } else if (!silent) {
                    if (!isAuthFailureStatus(status)) {
                        this.handleError(error);
                    }
                }

                if (!silent && !isAuthFailureStatus(status)) {
                    throw error;
                }

                return null;
            } finally {
                this.loading = false;
            }
        },

        async updateProfile(payload: UpdateProfilePayload) {
            this.loading = true;
            this.clearError();

            try {
                const { $api } = useNuxtApp();
                const feedback = useFeedback();
                const { data } = await $api.put('/profile/me', payload);

                this.user = data.user;
                feedback.success('Профиль обновлён');
                return data.user as User;
            } catch (error: any) {
                this.handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async changePassword(payload: ChangePasswordPayload) {
            this.loading = true;
            this.clearError();

            try {
                const { $api } = useNuxtApp();
                const feedback = useFeedback();
                await $api.put('/profile/password', payload);
                feedback.success('Пароль успешно изменён');
                return true;
            } catch (error: any) {
                this.handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        handleError(error: any) {
            const feedback = useFeedback();

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
            feedback.failure(message);
        }
    }
})

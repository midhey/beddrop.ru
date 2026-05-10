import axios from 'axios';
import {useAuthStore} from "~/stores/auth";

const canAttemptSessionRefresh = (url?: string): boolean => {
    return url !== '/auth/refresh'
        && url !== '/auth/login'
        && url !== '/auth/register';
};

export default defineNuxtPlugin((nuxtApp) => {
    const config = useRuntimeConfig();

    const api = axios.create({
        baseURL: config.public.apiBase,
        withCredentials: true,
    });

    api.interceptors.request.use((request) => {
        const authStore = useAuthStore();
        if (authStore.accessToken) {
            request.headers = request.headers || {};
            request.headers.Authorization = `Bearer ${authStore.accessToken}`;
        }
        return request;
    });

    api.interceptors.response.use(
        (response) => response,
        async (error) => {
            const authStore = useAuthStore();
            const originalRequest = error.config;

            const status = error?.response?.status;

            if (!status || !originalRequest) {
                return Promise.reject(error);
            }

            if (
                status === 401 &&
                canAttemptSessionRefresh(originalRequest?.url) &&
                !originalRequest._retry &&
                (!authStore.isReady || authStore.isAuthenticated)
            ) {
                originalRequest._retry = true;

                try {
                    await authStore.refresh(true);

                    if (authStore.accessToken) {
                        originalRequest.headers = originalRequest.headers || {};
                        originalRequest.headers.Authorization = `Bearer ${authStore.accessToken}`;
                    } else {
                        return Promise.reject(error);
                    }

                    return api(originalRequest);
                } catch (refreshError: any) {
                    const refreshStatus = refreshError?.response?.status;

                    if (refreshStatus === 401 || refreshStatus === 403) {
                        authStore.clearAuth();
                    }

                    return Promise.reject(refreshError);
                }
            }

            return Promise.reject(error);
        }
    );

    return {
        provide: {
            api,
        },
    };
});

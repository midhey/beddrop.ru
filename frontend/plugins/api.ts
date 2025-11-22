import axios from 'axios';
import {useAuthStore} from "~/stores/auth";

export default defineNuxtPlugin((nuxtApp) => {
    const config = useRuntimeConfig();

    const api = axios.create({
        baseURL: config.public.apiBase,
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

            if (!status) {
                return Promise.reject(error);
            }

            const isLoggedIn = !!authStore.accessToken;

            if (
                status === 401 &&
                isLoggedIn &&
                !originalRequest._retry &&
                originalRequest.url !== '/auth/refresh'
            ) {
                originalRequest._retry = true;

                try {
                    await authStore.refresh();

                    if (authStore.accessToken) {
                        originalRequest.headers = originalRequest.headers || {};
                        originalRequest.headers.Authorization = `Bearer ${authStore.accessToken}`;
                    }

                    return api(originalRequest);
                } catch (e) {
                    authStore.clearAuth();
                    return Promise.reject(e);
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
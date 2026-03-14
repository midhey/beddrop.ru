import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { Order } from '~/composables/useOrders';

const FINAL_ORDER_STATUSES = [
    'DELIVERED',
    'CANCELED_BY_USER',
    'CANCELED_BY_RESTAURANT',
];

let bootstrapPromise: Promise<void> | null = null;
let bootstrapRevision = 0;

export const useAppShellStore = defineStore('app-shell', () => {
    const hasRestaurantsAccess = ref(false);
    const hasCourierAccess = ref(false);
    const activeOrder = ref<Order | null>(null);

    const bootstrapping = ref(false);
    const bootstrappedForAuth = ref(false);
    const activeOrderLoading = ref(false);

    const isRevisionActive = (revision: number) => {
        const authStore = useAuthStore();
        return revision === bootstrapRevision && authStore.isAuthenticated;
    };

    const resetForGuest = () => {
        const cartStore = useCartStore();

        bootstrapRevision += 1;
        bootstrapPromise = null;

        hasRestaurantsAccess.value = false;
        hasCourierAccess.value = false;
        activeOrder.value = null;
        activeOrderLoading.value = false;
        bootstrapping.value = false;
        bootstrappedForAuth.value = false;

        cartStore.setCart(null);
        cartStore.setError(null);
    };

    const loadRestaurantsAccess = async (revision: number) => {
        const authStore = useAuthStore();

        if (!authStore.isAuthenticated) {
            if (revision === bootstrapRevision) {
                hasRestaurantsAccess.value = false;
            }
            return false;
        }

        try {
            const { $api } = useNuxtApp();
            const { data } = await $api.get<{ restaurants?: unknown[] }>('/restaurants/my');
            const hasAccess =
                Array.isArray(data.restaurants) && data.restaurants.length > 0;

            if (isRevisionActive(revision)) {
                hasRestaurantsAccess.value = hasAccess;
            }

            return hasAccess;
        } catch {
            if (isRevisionActive(revision)) {
                hasRestaurantsAccess.value = false;
            }

            return false;
        }
    };

    const loadCourierAccess = async (revision: number) => {
        const authStore = useAuthStore();

        if (!authStore.isAuthenticated) {
            if (revision === bootstrapRevision) {
                hasCourierAccess.value = false;
            }
            return false;
        }

        try {
            const { $api } = useNuxtApp();
            const { data } = await $api.get<any>('/courier/profile');
            const profile = data?.profile ?? data;
            const hasAccess = !!profile && profile.status !== 'SUSPENDED';

            if (isRevisionActive(revision)) {
                hasCourierAccess.value = hasAccess;
            }

            return hasAccess;
        } catch {
            if (isRevisionActive(revision)) {
                hasCourierAccess.value = false;
            }

            return false;
        }
    };

    const loadActiveOrder = async (revision: number) => {
        const authStore = useAuthStore();

        if (!authStore.isAuthenticated) {
            if (revision === bootstrapRevision) {
                activeOrder.value = null;
                activeOrderLoading.value = false;
            }
            return null;
        }

        activeOrderLoading.value = true;

        try {
            const { $api } = useNuxtApp();
            const { data } = await $api.get<{ data?: Order[] }>('/orders', {
                params: { per_page: 20 },
            });
            const orders = Array.isArray(data?.data) ? data.data : [];
            const nextActiveOrder =
                orders.find((order) => !FINAL_ORDER_STATUSES.includes(order.status)) ?? null;

            if (isRevisionActive(revision)) {
                activeOrder.value = nextActiveOrder;
            }

            return nextActiveOrder;
        } catch {
            if (isRevisionActive(revision)) {
                activeOrder.value = null;
            }

            return null;
        } finally {
            if (revision === bootstrapRevision) {
                activeOrderLoading.value = false;
            }
        }
    };

    const ensureBootstrapped = async (force = false) => {
        const authStore = useAuthStore();

        if (!authStore.isAuthenticated) {
            resetForGuest();
            return;
        }

        if (bootstrappedForAuth.value && !force) {
            return;
        }

        if (bootstrapPromise) {
            return bootstrapPromise;
        }

        const revision = ++bootstrapRevision;
        const cartStore = useCartStore();

        bootstrapping.value = true;

        const currentPromise = (async () => {
            if (!authStore.user) {
                await authStore.profile(true).catch(() => {
                });
            }

            if (!authStore.isAuthenticated) {
                resetForGuest();
                return;
            }

            await Promise.all([
                loadRestaurantsAccess(revision),
                loadCourierAccess(revision),
                loadActiveOrder(revision),
                cartStore.fetchCart().catch(() => {
                }),
            ]);

            if (!isRevisionActive(revision) && !authStore.isAuthenticated) {
                cartStore.setCart(null);
                cartStore.setError(null);
                return;
            }

            if (isRevisionActive(revision)) {
                bootstrappedForAuth.value = true;
            }
        })().finally(() => {
            if (revision === bootstrapRevision) {
                bootstrapping.value = false;
            }

            if (bootstrapPromise === currentPromise) {
                bootstrapPromise = null;
            }
        });

        bootstrapPromise = currentPromise;

        return bootstrapPromise;
    };

    return {
        hasRestaurantsAccess,
        hasCourierAccess,
        activeOrder,
        bootstrapping,
        bootstrappedForAuth,
        activeOrderLoading,
        resetForGuest,
        ensureBootstrapped,
    };
});

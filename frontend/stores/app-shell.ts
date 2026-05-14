import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { Order } from '~/composables/useOrders';
import type { Cart } from '~/stores/cart';
import type { User } from '~/stores/auth';


interface BootstrapResponse {
    user: User;
    has_restaurants_access: boolean;
    has_courier_access: boolean;
    active_order: Order | null;
    cart_summary: Cart | null;
}

interface ActiveOrderResponse {
    order: Order | null;
}

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

    const applyBootstrap = (data: BootstrapResponse, revision: number) => {
        const authStore = useAuthStore();
        const cartStore = useCartStore();

        if (!isRevisionActive(revision)) {
            return;
        }

        authStore.setUser(data.user);
        hasRestaurantsAccess.value = !!data.has_restaurants_access;
        hasCourierAccess.value = !!data.has_courier_access;
        activeOrder.value = data.active_order;
        activeOrderLoading.value = false;
        cartStore.setCartSummary(data.cart_summary);
        cartStore.setError(null);
        bootstrappedForAuth.value = true;
    };

    const loadBootstrap = async (revision: number) => {
        const authStore = useAuthStore();

        if (!authStore.isAuthenticated) {
            resetForGuest();
            return;
        }

        activeOrderLoading.value = true;

        try {
            const { $api } = useNuxtApp();
            const { data } = await $api.get<BootstrapResponse>('/me/bootstrap');
            applyBootstrap(data, revision);
        } catch (error: any) {
            if (isRevisionActive(revision)) {
                hasRestaurantsAccess.value = false;
                hasCourierAccess.value = false;
                activeOrder.value = null;
            }

            throw error;
        } finally {
            if (revision === bootstrapRevision) {
                activeOrderLoading.value = false;
            }
        }
    };

    const refreshActiveOrder = async () => {
        const authStore = useAuthStore();

        if (!authStore.isAuthenticated) {
            activeOrder.value = null;
            activeOrderLoading.value = false;
            return null;
        }

        const revision = bootstrapRevision;
        activeOrderLoading.value = true;

        try {
            const { $api } = useNuxtApp();
            const { data } = await $api.get<ActiveOrderResponse>('/orders/active');

            if (isRevisionActive(revision)) {
                activeOrder.value = data.order;
            }

            return data.order;
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
        await authStore.ensureSession();

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
            if (!authStore.isAuthenticated) {
                resetForGuest();
                return;
            }

            await loadBootstrap(revision);

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
        refreshActiveOrder,
    };
});

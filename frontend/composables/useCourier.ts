import { ref, computed } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import {
    assignCourierOrder,
    endCourierShift,
    getCourierProfile,
    getCurrentCourierShift,
    listCourierOrders,
    markCourierOrderDelivered,
    markCourierOrderPickedUp,
    startCourierShift,
} from '~/domains/courier/api';
import { isCourierShiftOpen } from '~/domains/courier/presentation';
import type {
    CourierOrder,
    CourierProfile,
    CourierShift,
} from '~/domains/courier/types';

export function useCourier() {
    const { handleApiError, errorMessage } = useApiHelpers();

    const profile = ref<CourierProfile | null>(null);
    const shift = ref<CourierShift | null>(null);

    const loadingProfile = ref(false);
    const loadingShift = ref(false);
    const loadingOrders = ref(false);

    const availableOrders = ref<CourierOrder[]>([]);
    const activeOrders = ref<CourierOrder[]>([]);
    const historyOrders = ref<CourierOrder[]>([]);

    const ordersBlockedByShift = ref(false);

    const hasActiveShift = computed(() => {
        return isCourierShiftOpen(shift.value);
    });

    const clearOrders = () => {
        availableOrders.value = [];
        activeOrders.value = [];
        historyOrders.value = [];
    };

    const fetchProfile = async () => {
        loadingProfile.value = true;
        errorMessage.value = null;

        try {
            profile.value = await getCourierProfile();
            return profile.value;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loadingProfile.value = false;
        }
    };

    const fetchShift = async () => {
        loadingShift.value = true;
        errorMessage.value = null;

        try {
            shift.value = await getCurrentCourierShift();
            return shift.value;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loadingShift.value = false;
        }
    };

    const startShift = async () => {
        loadingShift.value = true;
        errorMessage.value = null;

        try {
            shift.value = await startCourierShift();
            return shift.value;
        } catch (e: any) {
            if (e?.response?.status === 422) {
                try {
                    await fetchShift();
                    return shift.value;
                } catch {
                }
            }

            handleApiError(e);
            throw e;
        } finally {
            loadingShift.value = false;
        }
    };

    const endShift = async () => {
        if (!shift.value) return;

        loadingShift.value = true;
        errorMessage.value = null;

        try {
            shift.value = await endCourierShift();
            return shift.value;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loadingShift.value = false;
        }
    };

    const fetchOrders = async () => {
        loadingOrders.value = true;
        errorMessage.value = null;
        ordersBlockedByShift.value = false;

        try {
            const response = await listCourierOrders();
            availableOrders.value = response.available;
            activeOrders.value = response.active;
            historyOrders.value = response.history;
            ordersBlockedByShift.value = response.ordersBlockedByShift;

            return {
                available: availableOrders.value,
                active: activeOrders.value,
                history: historyOrders.value,
            };
        } catch (e) {
            clearOrders();
            handleApiError(e);
            throw e;
        } finally {
            loadingOrders.value = false;
        }
    };

    const updateOrderInLists = (order: CourierOrder) => {
        const id = order.id;

        const removeById = (list: CourierOrder[]) => list.filter((o) => o.id !== id);

        availableOrders.value = removeById(availableOrders.value);
        activeOrders.value = removeById(activeOrders.value);
        historyOrders.value = removeById(historyOrders.value);

        if (order.status === 'DELIVERED') {
            historyOrders.value = [order, ...historyOrders.value];
        } else if (['COURIER_ASSIGNED', 'PICKED_UP'].includes(order.status)) {
            activeOrders.value = [order, ...activeOrders.value];
        } else {
            availableOrders.value = [order, ...availableOrders.value];
        }
    };

    const assignOrder = async (orderId: number) => {
        errorMessage.value = null;

        try {
            const updated = await assignCourierOrder(orderId);
            updateOrderInLists(updated);
            return updated;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const markPickedUp = async (orderId: number) => {
        errorMessage.value = null;

        try {
            const updated = await markCourierOrderPickedUp(orderId);
            updateOrderInLists(updated);
            return updated;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const markDelivered = async (orderId: number) => {
        errorMessage.value = null;

        try {
            const updated = await markCourierOrderDelivered(orderId);
            updateOrderInLists(updated);
            return updated;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    return {
        profile,
        shift,
        hasActiveShift,
        ordersBlockedByShift,

        loadingProfile,
        loadingShift,
        loadingOrders,
        errorMessage,

        availableOrders,
        activeOrders,
        historyOrders,

        // methods
        fetchProfile,
        fetchShift,
        startShift,
        endShift,
        fetchOrders,
        assignOrder,
        markPickedUp,
        markDelivered,
    };
}

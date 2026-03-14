// composables/useRestaurantOrders.ts
import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import type { Order } from '~/composables/useOrders';
import {
    acceptRestaurantOrder,
    cancelRestaurantOrder,
    listRestaurantOrders,
} from '~/domains/restaurants/manage/api';
import type { PaginationMeta } from '~/utils/api/pagination';

export function useRestaurantOrders() {
    const { handleApiError, errorMessage } = useApiHelpers();

    const items = ref<Order[]>([]);
    const pagination = ref<PaginationMeta | null>(null);
    const loading = ref(false);

    const fetchOrders = async (
        restaurantSlug: string,
        params: Record<string, any> = {},
    ) => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const response = await listRestaurantOrders(restaurantSlug, params);
            items.value = response.items;
            pagination.value = response.pagination;
            return response;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const acceptOrder = async (
        restaurantSlug: string,
        orderId: number,
    ): Promise<Order> => {
        errorMessage.value = null;

        try {
            const updated = await acceptRestaurantOrder(restaurantSlug, orderId);

            items.value = items.value.map((o) =>
                o.id === updated.id ? updated : o,
            );

            return updated;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const cancelOrder = async (
        restaurantSlug: string,
        orderId: number,
        reason?: string,
    ): Promise<Order> => {
        errorMessage.value = null;

        try {
            const updated = await cancelRestaurantOrder(
                restaurantSlug,
                orderId,
                reason,
            );

            items.value = items.value.map((o) =>
                o.id === updated.id ? updated : o,
            );

            return updated;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    return {
        items,
        pagination,
        loading,
        errorMessage,
        fetchOrders,
        acceptOrder,
        cancelOrder,
    };
}

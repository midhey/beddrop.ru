import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import { getActiveOrder } from '~/domains/orders/api';
import type { Order } from '~/composables/useOrders';

export function useActiveOrder() {
    const { handleApiError, errorMessage } = useApiHelpers();

    const activeOrder = ref<Order | null>(null);
    const loading = ref(false);

    const fetchActiveOrder = async () => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const nextActiveOrder = await getActiveOrder();
            activeOrder.value = nextActiveOrder;
            return nextActiveOrder;
        } catch (e) {
            handleApiError(e);
            activeOrder.value = null;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const clearActiveOrder = () => {
        activeOrder.value = null;
    };

    return {
        activeOrder,
        loading,
        errorMessage,
        fetchActiveOrder,
        clearActiveOrder,
    };
}

import { computed, onMounted } from 'vue';
import { useSeoMeta } from '#imports';
import { useOrders } from '~/composables/useOrders';
import {
    getOrderStatusClass,
    getOrderStatusLabel,
    getPaymentStatusLabel,
} from '~/domains/orders/presentation';
import { formatDateTime, formatPrice } from '~/utils/formatting';

export function useOrdersListPage() {
    const { items, loading, errorMessage, fetchOrders } = useOrders();

    useSeoMeta({
        title: 'Мои заказы — BedDrop',
    });

    const hasOrders = computed(() => items.value.length > 0);

    const loadOrders = async () => {
        try {
            await fetchOrders();
        } catch {
        }
    };

    onMounted(loadOrders);

    return {
        items,
        loading,
        errorMessage,
        hasOrders,
        formatPrice,
        formatDateTime,
        getOrderStatusClass,
        getOrderStatusLabel,
        getPaymentStatusLabel,
    };
}

import { computed, onMounted } from 'vue';
import { useOrders } from '~/composables/useOrders';
import {
    getOrderStatusClass,
    getOrderStatusLabel,
    getPaymentStatusLabel,
} from '~/domains/orders/presentation';
import { formatDateTime, formatPrice } from '~/utils/formatting';

export function useOrdersListPage() {
    const { items, loading, errorMessage, fetchOrders } = useOrders();

    useAppSeoMeta({
        title: 'Мои заказы — BedDrop',
        description: 'История и статусы ваших заказов в BedDrop: оплата, доставка, маршрут и события заказа.',
        robots: 'noindex,nofollow',
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

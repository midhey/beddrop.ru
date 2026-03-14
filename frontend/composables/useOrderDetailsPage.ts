import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useOrders } from '~/composables/useOrders';
import {
    getOrderStatusClass,
    getOrderStatusLabel,
    getPaymentMethodLabel,
    getPaymentStatusLabel,
    sortOrderEvents,
} from '~/domains/orders/presentation';
import { formatDateTime, formatPrice } from '~/utils/formatting';

export function useOrderDetailsPage() {
    const route = useRoute();
    const router = useRouter();
    const { current, currentLoading, errorMessage, fetchOrder } = useOrders();

    const id = computed(() => Number(route.params.id));

    useSeoMeta(() => ({
        title: current.value
            ? `Заказ #${current.value.id} — BedDrop`
            : 'Заказ — BedDrop',
    }));

    const sortedEvents = computed(() => sortOrderEvents(current.value?.events));

    const loadOrder = async () => {
        if (!id.value || Number.isNaN(id.value)) {
            await router.push('/orders');
            return;
        }

        try {
            await fetchOrder(id.value);
        } catch (error: any) {
            if (error?.response?.status === 404) {
                await router.push('/orders');
            }
        }
    };

    onMounted(loadOrder);

    return {
        current,
        currentLoading,
        errorMessage,
        id,
        sortedEvents,
        formatPrice,
        formatDateTime,
        getOrderStatusClass,
        getOrderStatusLabel,
        getPaymentMethodLabel,
        getPaymentStatusLabel,
    };
}

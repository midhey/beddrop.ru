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
    const routeDistanceKm = computed(() => {
        if (!current.value?.delivery_distance_meters) return null;

        return (current.value.delivery_distance_meters / 1000).toFixed(1);
    });
    const deliveryDurationMinutes = computed(() => {
        if (!current.value?.delivery_duration_seconds) return null;

        return Math.max(1, Math.ceil(current.value.delivery_duration_seconds / 60));
    });
    const logisticsTimeBreakdown = computed(() => {
        const time = current.value?.logistics_snapshot?.time;

        if (!time) return [];

        return [
            { label: 'Готовка ресторана', value: time.prep },
            { label: 'Буфер на выдачу', value: time.pickup_buffer },
            { label: 'Маршрут курьера', value: time.delivery },
            { label: 'Запас доставки', value: time.buffer },
            { label: 'Итого до двери', value: time.total, total: true },
        ].filter((item) => typeof item.value === 'number' && item.value > 0);
    });

    const isDelayed = computed(() => {
        if (
            !current.value?.estimated_delivery_at ||
            current.value.payment_status !== 'PAID' ||
            ['DELIVERED', 'CANCELED_BY_USER', 'CANCELED_BY_RESTAURANT'].includes(current.value.status)
        ) {
            return false;
        }

        const eta = new Date(current.value.estimated_delivery_at).getTime();
        const now = new Date().getTime();
        return now > eta;
    });

    const isFinal = computed(() => {
        if (!current.value) return false;
        return ['DELIVERED', 'CANCELED_BY_USER', 'CANCELED_BY_RESTAURANT'].includes(current.value.status);
    });

    const getDeliveryProgress = (status: string, paymentStatus?: string) => {
        if (paymentStatus === 'PENDING') return 5;

        const stages: Record<string, number> = {
            'CREATED': 15,
            'ACCEPTED_BY_RESTAURANT': 35,
            'READY_FOR_PICKUP': 60,
            'COURIER_ASSIGNED': 75,
            'PICKED_UP': 90,
            'DELIVERED': 100,
            'CANCELED_BY_USER': 0,
            'CANCELED_BY_RESTAURANT': 0,
        };
        return stages[status] || 15;
    };

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
        routeDistanceKm,
        deliveryDurationMinutes,
        logisticsTimeBreakdown,
        isDelayed,
        isFinal,
        formatPrice,
        formatDateTime,
        getOrderStatusClass,
        getOrderStatusLabel,
        getPaymentMethodLabel,
        getPaymentStatusLabel,
        getDeliveryProgress,
    };
}

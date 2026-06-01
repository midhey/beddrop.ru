import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useFeedback } from '~/composables/useFeedback';
import { useOrders } from '~/composables/useOrders';
import { checkOrderPaymentStatusRequest } from '~/domains/orders/api';
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
    const {
        current,
        currentLoading,
        errorMessage,
        fetchOrder,
        initiatePayment,
        cancelOrder: cancelOrderAction,
    } = useOrders();
    const feedback = useFeedback();
    const paymentLoading = ref(false);
    const cancelLoading = ref(false);

    const id = computed(() => Number(route.params.id));

    const canCancel = computed(() => {
        if (!current.value) return false;
        return ['CREATED', 'ACCEPTED_BY_RESTAURANT'].includes(current.value.status);
    });

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

    const payOrder = async () => {
        if (!current.value || paymentLoading.value) return;

        paymentLoading.value = true;
        try {
            const response = await initiatePayment(current.value.id);
            if (response.payment.confirmation_url) {
                window.location.href = response.payment.confirmation_url;
                return;
            }

            await fetchOrder(current.value.id);
        } catch {
        } finally {
            paymentLoading.value = false;
        }
    };

    const refreshPayment = async () => {
        if (!current.value || paymentLoading.value) return;

        paymentLoading.value = true;
        try {
            const response = await checkOrderPaymentStatusRequest(current.value.id);

            current.value = {
                ...current.value,
                status: response.order.status,
                payment_status: response.order.payment_status,
            };

            if (response.result === 'missing') {
                feedback.info(response.message);
                return;
            }

            if (response.order.payment_status === 'PAID') {
                feedback.success(response.message);
                await fetchOrder(response.order.id);
                return;
            }

            if (response.order.payment_status === 'FAILED') {
                feedback.warning(response.message);
                return;
            }

            feedback.info(response.message);
        } catch (error: any) {
            const status = error?.response?.status;
            const message = status >= 500
                ? 'Сервис оплаты временно недоступен. Попробуйте еще раз через пару минут.'
                : error?.response?.data?.message || 'Не удалось проверить оплату. Попробуйте еще раз.';

            feedback.failure(message);
        } finally {
            paymentLoading.value = false;
        }
    };

    const cancelOrder = async () => {
        if (!current.value || cancelLoading.value) return;

        const confirmed = await feedback.confirm({
            title: 'Отмена заказа',
            message: 'Вы уверены, что хотите отменить заказ? Это действие нельзя будет отменить.',
            confirmText: 'Да, отменить',
            cancelText: 'Нет, оставить',
        });

        if (!confirmed) {
            return;
        }

        cancelLoading.value = true;
        try {
            const updatedOrder = await cancelOrderAction(current.value.id);
            current.value = updatedOrder;
            feedback.success('Заказ успешно отменен');
        } catch {
            // ошибка обработана в useOrders
        } finally {
            cancelLoading.value = false;
        }
    };

    onMounted(loadOrder);

    return {
        current,
        currentLoading,
        paymentLoading,
        cancelLoading,
        errorMessage,
        id,
        sortedEvents,
        routeDistanceKm,
        deliveryDurationMinutes,
        logisticsTimeBreakdown,
        isDelayed,
        isFinal,
        canCancel,
        formatPrice,
        formatDateTime,
        getOrderStatusClass,
        getOrderStatusLabel,
        getPaymentMethodLabel,
        getPaymentStatusLabel,
        getDeliveryProgress,
        payOrder,
        refreshPayment,
        cancelOrder,
    };
}

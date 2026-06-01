import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useFeedback } from '~/composables/useFeedback';
import { useCourier } from '~/composables/useCourier';
import {
    canCourierMarkDelivered,
    canCourierMarkPickedUp,
    formatCourierRating,
    getCourierDeliveryAddress,
    getCourierOrderStatusLabel,
    getCourierProfileStatusLabel,
    getCourierRestaurantAddress,
    getCourierVehicleLabel,
} from '~/domains/courier/presentation';
import type { CourierOrder } from '~/domains/courier/types';
import type { OrderRouteSegment } from '~/composables/useOrders';
import { formatDateTime, formatPrice } from '~/utils/formatting';

type CourierActionType = 'assign' | 'pickup' | 'deliver' | null;
type CourierLocationStatus = 'idle' | 'tracking' | 'paused' | 'denied' | 'error' | 'unsupported';

interface CourierLocationState {
    lat: number;
    lng: number;
    accuracy: number | null;
    heading: number | null;
    speed: number | null;
    recordedAt: string;
}

const LOCATION_ORDERS_REFRESH_INTERVAL_MS = 45000;

export function useCourierDashboardPage() {
    const router = useRouter();
    const {
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
        earnings,
        fetchProfile,
        fetchShift,
        fetchOrders,
        fetchEarnings,
        startShift,
        endShift,
        assignOrder,
        markPickedUp,
        markDelivered,
        sendLocation,
    } = useCourier();
    const feedback = useFeedback();

    useSeoMeta({
        title: 'Курьерский кабинет — BedDrop',
    });

    const pageLoading = computed(
        () => loadingProfile.value || loadingShift.value || loadingOrders.value,
    );

    const actionOrderId = ref<number | null>(null);
    const actionType = ref<CourierActionType>(null);
    const locationWatchId = ref<number | null>(null);
    const latestLocation = ref<CourierLocationState | null>(null);
    const locationStatus = ref<CourierLocationStatus>('idle');
    const locationError = ref('');
    const lastOrdersRefreshAt = ref(0);

    const profileStatusLabel = computed(() => {
        if (!profile.value) return '';
        return getCourierProfileStatusLabel(profile.value.status);
    });

    const vehicleLabel = computed(() =>
        getCourierVehicleLabel(profile.value?.vehicle),
    );

    const ratingText = computed(() => formatCourierRating(profile.value?.rating));

    const shiftSummary = computed(() => {
        if (!hasActiveShift.value) {
            return 'Смена не активна';
        }

        if (!shift.value?.started_at) {
            return 'Открыта с неизвестного времени';
        }

        return `Открыта с ${formatDateTime(shift.value.started_at)}`;
    });

    const locationStatusLabel = computed(() => {
        if (!hasActiveShift.value && locationStatus.value !== 'unsupported') {
            return 'Трекинг включится после начала смены';
        }

        const labels: Record<CourierLocationStatus, string> = {
            idle: 'Ожидаем координаты',
            tracking: 'Геолокация активна',
            paused: 'Трекинг на паузе',
            denied: 'Доступ к геолокации запрещён',
            error: 'Геолокация недоступна',
            unsupported: 'Браузер не поддерживает геолокацию',
        };

        return labels[locationStatus.value];
    });

    const locationUpdatedText = computed(() => {
        if (!latestLocation.value) return 'Координат пока нет';

        return `Обновлено ${formatDateTime(latestLocation.value.recordedAt)}`;
    });

    const locationAccuracyText = computed(() => {
        const accuracy = latestLocation.value?.accuracy;

        if (accuracy == null) return 'Точность неизвестна';

        return `Точность ${Math.round(accuracy)} м`;
    });

    const locationSpeedText = computed(() => {
        const speed = latestLocation.value?.speed;

        if (speed == null || speed < 0) return 'Скорость не определена';

        return `${Math.round(speed * 3.6)} км/ч`;
    });

    const withOrderAction = async (
        orderId: number,
        nextActionType: Exclude<CourierActionType, null>,
        action: () => Promise<void>,
    ) => {
        if (actionOrderId.value) return;

        actionOrderId.value = orderId;
        actionType.value = nextActionType;

        try {
            await action();
        } finally {
            actionOrderId.value = null;
            actionType.value = null;
        }
    };

    const startOrEndShift = async () => {
        try {
            if (hasActiveShift.value) {
                await endShift();
            } else {
                await startShift();
            }

            await fetchOrders();
        } catch {
        }
    };

    const doAssign = async (orderId: number) => {
        await withOrderAction(orderId, 'assign', async () => {
            await assignOrder(orderId);
        });
    };

    const doPickup = async (orderId: number) => {
        await withOrderAction(orderId, 'pickup', async () => {
            await markPickedUp(orderId);
        });
    };

    const doDeliver = async (orderId: number) => {
        await withOrderAction(orderId, 'deliver', async () => {
            await markDelivered(orderId);
            await fetchEarnings();
        });
    };

    const getRestaurantAddress = (order: CourierOrder): string => {
        return getCourierRestaurantAddress(order);
    };

    const getDeliveryAddress = (order: CourierOrder): string => {
        return getCourierDeliveryAddress(order);
    };

    const parseMoney = (value: string | number | null | undefined): number | null => {
        if (value == null) return null;

        const amount = typeof value === 'string' ? Number(value) : value;

        return Number.isFinite(amount) ? amount : null;
    };

    const getCourierPayout = (order: CourierOrder): string => {
        const finalFee = parseMoney(order.courier_fee);
        if (finalFee !== null && finalFee > 0) {
            return formatPrice(finalFee);
        }

        const estimatedFee = parseMoney(order.courier_estimated_fee);
        if (estimatedFee !== null && estimatedFee > 0) {
            return formatPrice(estimatedFee);
        }

        return formatPrice(order.delivery_price_snapshot ?? order.total_price);
    };

    const getOrderMoneyDetails = (order: CourierOrder): string => {
        const details = [`Общий чек ${formatPrice(order.total_price)}`];

        if (order.delivery_price_snapshot) {
            details.push(`доставка ${formatPrice(order.delivery_price_snapshot)}`);
        }

        return details.join(' · ');
    };

    const earningsCards = computed(() => {
        const data = earnings.value;

        return [
            { key: 'today', title: 'Сегодня', bucket: data?.today },
            { key: 'week', title: 'Неделя', bucket: data?.week },
            { key: 'total', title: 'Всё время', bucket: data?.total },
        ].map((item) => ({
            ...item,
            deliveries: item.bucket?.deliveries_count ?? 0,
            earnings: formatPrice(item.bucket?.earnings_sum ?? 0),
            turnover: formatPrice(item.bucket?.total_orders_sum ?? 0),
        }));
    });

    const showWithdrawPlaceholder = () => {
        feedback.info('Пока не работает');
    };

    const formatDistance = (meters: number | null | undefined): string => {
        if (meters == null) return '';
        if (meters < 1000) return `${Math.round(meters)} м`;
        return `${(meters / 1000).toFixed(1)} км`;
    };

    const formatDuration = (seconds: number | null | undefined): string => {
        if (seconds == null || seconds <= 0) return '';

        const minutes = Math.max(1, Math.ceil(seconds / 60));

        if (minutes < 60) return `${minutes} мин`;

        const hours = Math.floor(minutes / 60);
        const rest = minutes % 60;

        return rest ? `${hours} ч ${rest} мин` : `${hours} ч`;
    };

    const getSegmentDuration = (
        order: CourierOrder,
        segmentType: 'courier_to_restaurant' | 'restaurant_to_client',
    ): number | null => {
        const segment = order.route_segments?.find((item) => item.segment_type === segmentType);

        return segment?.duration_seconds ?? null;
    };

    const estimatedApproachDuration = (order: CourierOrder): number | null => {
        if (!order.courier_approach_distance_meters) return null;

        const speedKmh = {
            FOOT: 5.1,
            BIKE: 15,
            SCOOTER: 18,
            CAR: 30,
        }[profile.value?.vehicle ?? 'BIKE'];

        return order.courier_approach_distance_meters / (speedKmh * 1000 / 3600);
    };

    const getCourierRideTime = (order: CourierOrder): string => {
        const seconds = order.status === 'PICKED_UP'
            ? getSegmentDuration(order, 'restaurant_to_client') ?? order.delivery_duration_seconds
            : getSegmentDuration(order, 'courier_to_restaurant') ?? estimatedApproachDuration(order);

        const formatted = formatDuration(seconds);

        return formatted ? `Ехать примерно ${formatted}` : 'Время в пути уточняется';
    };

    const getDeliveryTime = (order: CourierOrder): string => {
        const seconds = getSegmentDuration(order, 'restaurant_to_client') ?? order.delivery_duration_seconds;
        const formatted = formatDuration(seconds);

        return formatted ? `Доставка ${formatted}` : '';
    };

    const getRouteSegmentsForOrder = (
        order: CourierOrder,
        context: 'available' | 'active',
    ): OrderRouteSegment[] => {
        const segments = order.route_segments ?? [];

        if (context === 'available') {
            return segments;
        }

        if (order.status === 'PICKED_UP') {
            return segments.filter((segment) => segment.segment_type === 'restaurant_to_client');
        }

        return segments.filter((segment) => segment.segment_type === 'courier_to_restaurant');
    };

    const goBack = () => {
        router.back();
    };

    const loadCourierDashboard = async () => {
        try {
            await Promise.all([fetchProfile(), fetchShift(), fetchOrders(), fetchEarnings()]);
            syncLocationWatch();
        } catch {
        }
    };

    const stopLocationWatch = () => {
        if (locationWatchId.value !== null && typeof navigator !== 'undefined' && navigator.geolocation) {
            navigator.geolocation.clearWatch(locationWatchId.value);
            locationWatchId.value = null;
        }
    };

    const handleLocationError = (error?: GeolocationPositionError) => {
        if (error?.code === error.PERMISSION_DENIED) {
            locationStatus.value = 'denied';
            locationError.value = 'Разрешите доступ к геолокации, чтобы заказы сортировались по расстоянию.';
            return;
        }

        locationStatus.value = 'error';
        locationError.value = 'Не удалось получить текущие координаты.';
    };

    const handleLocation = async (position: GeolocationPosition) => {
        const recordedAt = new Date(position.timestamp).toISOString();

        latestLocation.value = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy,
            heading: position.coords.heading,
            speed: position.coords.speed,
            recordedAt,
        };
        locationStatus.value = 'tracking';
        locationError.value = '';

        await sendLocation({
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy,
            heading: position.coords.heading,
            speed: position.coords.speed,
            recorded_at: recordedAt,
        });

        const now = Date.now();
        if (now - lastOrdersRefreshAt.value > LOCATION_ORDERS_REFRESH_INTERVAL_MS) {
            lastOrdersRefreshAt.value = now;
            try {
                await fetchOrders();
            } catch {
            }
        }
    };

    const requestCourierLocation = () => {
        if (typeof navigator === 'undefined' || !navigator.geolocation) {
            locationStatus.value = 'unsupported';
            return;
        }

        if (!hasActiveShift.value) {
            locationStatus.value = 'paused';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                void handleLocation(position);
            },
            handleLocationError,
            {
                enableHighAccuracy: true,
                maximumAge: 10000,
                timeout: 10000,
            },
        );

        syncLocationWatch();
    };

    const syncLocationWatch = () => {
        if (typeof navigator === 'undefined' || !navigator.geolocation) {
            locationStatus.value = 'unsupported';
            return;
        }

        if (!hasActiveShift.value) {
            stopLocationWatch();
            locationStatus.value = 'paused';
            return;
        }

        if (locationWatchId.value !== null) return;

        locationWatchId.value = navigator.geolocation.watchPosition(
            (position) => {
                void handleLocation(position);
            },
            handleLocationError,
            {
                enableHighAccuracy: true,
                maximumAge: 15000,
                timeout: 10000,
            },
        );
    };

    onMounted(loadCourierDashboard);
    watch(hasActiveShift, syncLocationWatch);
    onBeforeUnmount(() => {
        stopLocationWatch();
    });

    return {
        profile,
        shift,
        hasActiveShift,
        ordersBlockedByShift,
        loadingShift,
        errorMessage,
        availableOrders,
        activeOrders,
        historyOrders,
        earningsCards,
        pageLoading,
        actionOrderId,
        actionType,
        profileStatusLabel,
        vehicleLabel,
        ratingText,
        shiftSummary,
        latestLocation,
        locationStatus,
        locationStatusLabel,
        locationUpdatedText,
        locationAccuracyText,
        locationSpeedText,
        locationError,
        formatPrice,
        formatDateTime,
        getCourierOrderStatusLabel,
        getRestaurantAddress,
        getDeliveryAddress,
        getCourierPayout,
        getOrderMoneyDetails,
        getCourierRideTime,
        getDeliveryTime,
        getRouteSegmentsForOrder,
        formatDistance,
        canCourierMarkPickedUp,
        canCourierMarkDelivered,
        requestCourierLocation,
        startOrEndShift,
        doAssign,
        doPickup,
        doDeliver,
        showWithdrawPlaceholder,
        goBack,
    };
}

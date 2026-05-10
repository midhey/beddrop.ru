import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from '#app';
import { useSeoMeta } from '#imports';
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
import { formatDateTime, formatPrice } from '~/utils/formatting';

type CourierActionType = 'assign' | 'pickup' | 'deliver' | null;

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
        fetchProfile,
        fetchShift,
        fetchOrders,
        startShift,
        endShift,
        assignOrder,
        markPickedUp,
        markDelivered,
        sendLocation,
    } = useCourier();

    useSeoMeta({
        title: 'Курьерский кабинет — BedDrop',
    });

    const pageLoading = computed(
        () => loadingProfile.value || loadingShift.value || loadingOrders.value,
    );

    const actionOrderId = ref<number | null>(null);
    const actionType = ref<CourierActionType>(null);
    const locationWatchId = ref<number | null>(null);

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
        });
    };

    const getRestaurantAddress = (order: CourierOrder): string => {
        return getCourierRestaurantAddress(order);
    };

    const getDeliveryAddress = (order: CourierOrder): string => {
        return getCourierDeliveryAddress(order);
    };

    const formatDistance = (meters: number | null | undefined): string => {
        if (meters == null) return '';
        if (meters < 1000) return `${Math.round(meters)} м`;
        return `${(meters / 1000).toFixed(1)} км`;
    };

    const goBack = () => {
        router.back();
    };

    const loadCourierDashboard = async () => {
        try {
            await Promise.all([fetchProfile(), fetchShift(), fetchOrders()]);
            syncLocationWatch();
        } catch {
        }
    };

    const syncLocationWatch = () => {
        if (typeof navigator === 'undefined' || !navigator.geolocation) return;

        if (!hasActiveShift.value) {
            if (locationWatchId.value !== null) {
                navigator.geolocation.clearWatch(locationWatchId.value);
                locationWatchId.value = null;
            }
            return;
        }

        if (locationWatchId.value !== null) return;

        locationWatchId.value = navigator.geolocation.watchPosition(
            async (position) => {
                await sendLocation({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    heading: position.coords.heading,
                    speed: position.coords.speed,
                    recorded_at: new Date(position.timestamp).toISOString(),
                });
            },
            () => {},
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
        if (locationWatchId.value !== null && typeof navigator !== 'undefined' && navigator.geolocation) {
            navigator.geolocation.clearWatch(locationWatchId.value);
        }
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
        pageLoading,
        actionOrderId,
        actionType,
        profileStatusLabel,
        vehicleLabel,
        ratingText,
        shiftSummary,
        formatPrice,
        formatDateTime,
        getCourierOrderStatusLabel,
        getRestaurantAddress,
        getDeliveryAddress,
        formatDistance,
        canCourierMarkPickedUp,
        canCourierMarkDelivered,
        startOrEndShift,
        doAssign,
        doPickup,
        doDeliver,
        goBack,
    };
}

import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useAddresses } from '~/composables/useAddresses';
import { useFeedback } from '~/composables/useFeedback';
import { useOrders, type CreateOrderPayload } from '~/composables/useOrders';
import type { OrderRouteSegment } from '~/composables/useOrders';
import { useDeliveryQuote } from '~/composables/useDeliveryQuote';
import { useCartStore } from '~/stores/cart';
import { getRestaurantAvailabilityLabel } from '~/domains/restaurants/presentation';
import { formatPrice } from '~/utils/formatting';

export function useCheckoutPage() {
    useSeoMeta({
        title: 'Оформление заказа — BedDrop',
    });

    const router = useRouter();
    const feedback = useFeedback();
    const cartStore = useCartStore();
    const {
        items: addresses,
        loading: addressesLoading,
        errorMessage: addressesError,
        fetchAddresses,
    } = useAddresses();

    const {
        createOrder,
        errorMessage: orderError,
        loading: ordersLoading,
    } = useOrders();
    const {
        quote: deliveryQuote,
        loading: quoteLoading,
        errorMessage: quoteError,
        fetchQuote,
        resetQuote,
    } = useDeliveryQuote();

    const selectedAddressId = ref<number | null>(null);
    const paymentMethod = ref<'CASH' | 'CARD' | 'ONLINE'>('CASH');
    const comment = ref('');
    const submitting = ref(false);

    const cart = computed(() => cartStore.cart);
    const cartLoading = computed(() => cartStore.loading || !!cartStore.cart?.is_summary);
    const cartError = computed(() => cartStore.error);

    const pageLoading = computed(
        () =>
            cartLoading.value ||
            addressesLoading.value ||
            submitting.value ||
            ordersLoading.value,
    );

    const isCartEmpty = computed(() => {
        return !cart.value || !cart.value.items || cart.value.items.length === 0;
    });

    const cartTotal = computed(() => cartStore.totalPrice);
    const deliveryPrice = computed(() => deliveryQuote.value?.delivery_price ?? 0);
    const deliveryRoutePrice = computed(() => {
        const price = deliveryQuote.value?.price;
        if (!price) return 0;

        return Number(price.base || 0) + Number(price.distance || 0);
    });
    const serviceFee = computed(() => deliveryQuote.value?.price.service ?? 0);
    const checkoutTotal = computed(() => cartTotal.value + deliveryPrice.value);
    const cartItemsCount = computed(() => cartStore.items.length);
    const restaurantName = computed(
        () => cartStore.restaurant?.name || 'Ресторан',
    );
    const restaurantAvailability = computed(() => cartStore.restaurant?.availability ?? null);
    const isRestaurantOpenForOrders = computed(() => restaurantAvailability.value?.is_open ?? true);
    const restaurantClosedText = computed(() => {
        if (isRestaurantOpenForOrders.value) return null;

        return getRestaurantAvailabilityLabel(cartStore.restaurant);
    });
    const restaurantAddress = computed(() => cartStore.restaurant?.address ?? null);
    const selectedAddress = computed(() => {
        return addresses.value.find((address) => address.id === selectedAddressId.value) ?? null;
    });
    const deliveryDistanceKm = computed(() => {
        if (!deliveryQuote.value) return null;

        return (deliveryQuote.value.distance_meters / 1000).toFixed(1);
    });
    const deliveryDurationMinutes = computed(() => {
        if (!deliveryQuote.value) return null;

        return Math.max(1, Math.ceil(deliveryQuote.value.duration_seconds / 60));
    });
    const deliveryTimeBreakdown = computed(() => {
        const time = deliveryQuote.value?.time;

        if (!time) return [];

        return [
            { label: 'Готовка ресторана', value: time.prep },
            { label: 'Буфер на выдачу', value: time.pickup_buffer },
            { label: 'Маршрут курьера', value: time.delivery },
            { label: 'Запас доставки', value: time.buffer },
        ].filter((item) => item.value > 0);
    });
    const quoteRouteSegments = computed<OrderRouteSegment[]>(() => {
        const quote = deliveryQuote.value;

        if (!quote?.route?.encoded_shape) return [];

        return [
            {
                id: 0,
                order_id: 0,
                segment_type: 'restaurant_to_client',
                mode: quote.mode,
                distance_meters: quote.route.distance_meters,
                duration_seconds: quote.route.duration_seconds,
                encoded_shape: quote.route.encoded_shape,
            },
        ];
    });

    const canSubmit = computed(
        () =>
            !isCartEmpty.value &&
            !!selectedAddressId.value &&
            !!deliveryQuote.value &&
            isRestaurantOpenForOrders.value &&
            !quoteLoading.value &&
            !submitting.value &&
            !ordersLoading.value,
    );

    const refreshDeliveryQuote = async () => {
        const restaurantId = cartStore.cart?.restaurant_id ?? cartStore.restaurant?.id;
        const addressId = selectedAddressId.value;

        if (!restaurantId || !addressId) {
            resetQuote();
            return;
        }

        try {
            await fetchQuote(restaurantId, addressId);
        } catch {
        }
    };

    const submitOrder = async () => {
        if (!canSubmit.value) return;

        submitting.value = true;

        try {
            await feedback.withBlock('.checkout-page__layout', async () => {
                const payload: CreateOrderPayload = {
                    payment_method: paymentMethod.value,
                    comment: comment.value || null,
                    delivery_address_id: selectedAddressId.value,
                };

                await createOrder(payload);
                await cartStore.clearCart();
                await router.push('/orders');
            }, 'Оформляем заказ...');
        } finally {
            submitting.value = false;
        }
    };

    const init = async () => {
        await Promise.all([cartStore.fetchCart(), fetchAddresses()]);

        if (addresses.value.length) {
            selectedAddressId.value = addresses.value[0].id;
        }

        await refreshDeliveryQuote();
    };

    onMounted(init);

    watch(selectedAddressId, async () => {
        await refreshDeliveryQuote();
    });

    return {
        addresses,
        addressesError,
        orderError,
        quoteError,
        cart,
        cartError,
        pageLoading,
        isCartEmpty,
        selectedAddressId,
        paymentMethod,
        comment,
        cartTotal,
        deliveryQuote,
        quoteLoading,
        deliveryPrice,
        deliveryRoutePrice,
        serviceFee,
        checkoutTotal,
        cartItemsCount,
        restaurantName,
        restaurantClosedText,
        restaurantAddress,
        selectedAddress,
        deliveryDistanceKm,
        deliveryDurationMinutes,
        deliveryTimeBreakdown,
        quoteRouteSegments,
        canSubmit,
        submitOrder,
        formatPrice,
    };
}

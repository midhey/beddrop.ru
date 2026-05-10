import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useAddresses } from '~/composables/useAddresses';
import { useFeedback } from '~/composables/useFeedback';
import { useOrders, type CreateOrderPayload } from '~/composables/useOrders';
import { useDeliveryQuote } from '~/composables/useDeliveryQuote';
import { useCartStore } from '~/stores/cart';
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
    const checkoutTotal = computed(() => cartTotal.value + deliveryPrice.value);
    const cartItemsCount = computed(() => cartStore.items.length);
    const restaurantName = computed(
        () => cartStore.restaurant?.name || 'Ресторан',
    );

    const canSubmit = computed(
        () =>
            !isCartEmpty.value &&
            !!selectedAddressId.value &&
            !quoteLoading.value &&
            !submitting.value &&
            !ordersLoading.value,
    );

    const refreshDeliveryQuote = async () => {
        const restaurantId = cartStore.cart?.restaurant_id;
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
        checkoutTotal,
        cartItemsCount,
        restaurantName,
        canSubmit,
        submitOrder,
        formatPrice,
    };
}

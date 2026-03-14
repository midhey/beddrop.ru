import { computed, onMounted, ref } from 'vue';
import { useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useAddresses } from '~/composables/useAddresses';
import { useFeedback } from '~/composables/useFeedback';
import { useOrders, type CreateOrderPayload } from '~/composables/useOrders';
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

    const selectedAddressId = ref<number | null>(null);
    const paymentMethod = ref<'CASH' | 'CARD' | 'ONLINE'>('CASH');
    const comment = ref('');
    const submitting = ref(false);

    const cart = computed(() => cartStore.cart);
    const cartLoading = computed(() => cartStore.loading);
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
    const cartItemsCount = computed(() => cartStore.items.length);
    const restaurantName = computed(
        () => cartStore.restaurant?.name || 'Ресторан',
    );

    const canSubmit = computed(
        () =>
            !isCartEmpty.value &&
            !!selectedAddressId.value &&
            !submitting.value &&
            !ordersLoading.value,
    );

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
    };

    onMounted(init);

    return {
        addresses,
        addressesError,
        orderError,
        cart,
        cartError,
        pageLoading,
        isCartEmpty,
        selectedAddressId,
        paymentMethod,
        comment,
        cartTotal,
        cartItemsCount,
        restaurantName,
        canSubmit,
        submitOrder,
        formatPrice,
    };
}

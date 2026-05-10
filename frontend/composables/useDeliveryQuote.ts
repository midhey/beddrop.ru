import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';

export interface DeliveryQuote {
    restaurant_id: number;
    delivery_address_id: number;
    mode: string;
    distance_meters: number;
    duration_seconds: number;
    prep_time_minutes: number;
    eta_minutes: number;
    delivery_price: number;
    price: {
        base: number;
        distance: number;
        service: number;
        total: number;
    };
    time: {
        prep: number;
        pickup_buffer: number;
        delivery: number;
        buffer: number;
        total: number;
    };
}

export function useDeliveryQuote() {
    const { $api } = useNuxtApp();
    const { handleApiError, errorMessage } = useApiHelpers();
    const quote = ref<DeliveryQuote | null>(null);
    const loading = ref(false);

    const fetchQuote = async (restaurantId: number, deliveryAddressId: number): Promise<DeliveryQuote> => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.post<{ quote: DeliveryQuote }>('/delivery/quote', {
                restaurant_id: restaurantId,
                delivery_address_id: deliveryAddressId,
            });
            quote.value = data.quote;
            return data.quote;
        } catch (e) {
            quote.value = null;
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const resetQuote = () => {
        quote.value = null;
        errorMessage.value = null;
    };

    return {
        quote,
        loading,
        errorMessage,
        fetchQuote,
        resetQuote,
    };
}

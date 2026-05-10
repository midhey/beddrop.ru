import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import type { AddressPayload } from '~/composables/useAddresses';

export interface AddressSuggestion {
    value: string | null;
    unrestricted_value: string | null;
    data: AddressPayload;
    raw: Record<string, any>;
}

export function useGeo() {
    const { $api } = useNuxtApp();
    const { handleApiError, errorMessage } = useApiHelpers();
    const loading = ref(false);

    const fetchAddressSuggestions = async (query: string): Promise<AddressSuggestion[]> => {
        if (query.trim().length < 2) return [];

        loading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.get<{ suggestions: AddressSuggestion[] }>('/geo/address-suggestions', {
                params: { q: query },
            });

            return data.suggestions;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const reverseGeocode = async (lat: number, lng: number): Promise<AddressSuggestion | null> => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.post<{ address: AddressSuggestion }>('/geo/reverse-geocode', {
                lat,
                lng,
            });

            return data.address;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        errorMessage,
        fetchAddressSuggestions,
        reverseGeocode,
    };
}

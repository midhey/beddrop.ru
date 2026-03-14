import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';

export interface Address {
    id: number;
    label: string | null;
    line1: string;
    line2: string | null;
    city: string | null;
    postal_code: string | null;
    lat: number | null;
    lng: number | null;
    created_at: string;
    updated_at: string;
}

export interface AddressPayload {
    label?: string | null;
    line1: string;
    line2?: string | null;
    city?: string | null;
    postal_code?: string | null;
    lat?: number | null;
    lng?: number | null;
}

export function useAddresses() {
    const { $api } = useNuxtApp();
    const { handleApiError, errorMessage } = useApiHelpers();

    const items = ref<Address[]>([]);
    const loading = ref(false);

    const fetchAddresses = async () => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.get<{ addresses: Address[] }>('/addresses');
            items.value = data.addresses;
            return data.addresses;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const createAddress = async (payload: AddressPayload): Promise<Address> => {
        errorMessage.value = null;

        try {
            const { data } = await $api.post<{ address: Address }>('/addresses', payload);
            const addr = data.address;
            items.value = [addr, ...items.value];
            return addr;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const updateAddress = async (
        id: number,
        payload: Partial<AddressPayload>,
    ): Promise<Address> => {
        errorMessage.value = null;

        try {
            const { data } = await $api.put<{ address: Address }>(`/addresses/${id}`, payload);
            const updated = data.address;
            items.value = items.value.map((a) => (a.id === id ? updated : a));
            return updated;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const deleteAddress = async (id: number): Promise<void> => {
        errorMessage.value = null;

        try {
            await $api.delete(`/addresses/${id}`);
            items.value = items.value.filter((a) => a.id !== id);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    return {
        items,
        loading,
        errorMessage,
        fetchAddresses,
        createAddress,
        updateAddress,
        deleteAddress,
    };
}
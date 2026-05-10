import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';

export interface Address {
    id: number;
    label: string | null;
    value: string | null;
    unrestricted_value: string | null;
    line1: string | null;
    line2: string | null;
    city: string | null;
    postal_code: string | null;
    country?: string | null;
    country_iso_code?: string | null;
    federal_district?: string | null;
    region_fias_id?: string | null;
    region_kladr_id?: string | null;
    region_iso_code?: string | null;
    region_with_type?: string | null;
    region_type?: string | null;
    region_type_full?: string | null;
    region?: string | null;
    area_fias_id?: string | null;
    area_kladr_id?: string | null;
    area_with_type?: string | null;
    area_type?: string | null;
    area_type_full?: string | null;
    area?: string | null;
    city_fias_id?: string | null;
    city_kladr_id?: string | null;
    city_with_type?: string | null;
    city_type?: string | null;
    city_type_full?: string | null;
    settlement_fias_id?: string | null;
    settlement_kladr_id?: string | null;
    settlement_with_type?: string | null;
    settlement_type?: string | null;
    settlement_type_full?: string | null;
    settlement?: string | null;
    street_fias_id?: string | null;
    street_kladr_id?: string | null;
    street_with_type?: string | null;
    street_type?: string | null;
    street_type_full?: string | null;
    street?: string | null;
    house_fias_id?: string | null;
    house_kladr_id?: string | null;
    house_type?: string | null;
    house_type_full?: string | null;
    house?: string | null;
    block_type?: string | null;
    block_type_full?: string | null;
    block?: string | null;
    flat_type?: string | null;
    flat_type_full?: string | null;
    flat?: string | null;
    entrance?: string | null;
    floor?: string | null;
    intercom?: string | null;
    lat: number | null;
    lng: number | null;
    fias_id?: string | null;
    kladr_id?: string | null;
    qc_geo?: number | null;
    timezone?: string | null;
    beltway_hit?: string | null;
    beltway_distance?: string | null;
    metro?: any[] | null;
    raw_dadata?: Record<string, any> | null;
    raw_dadata_json?: Record<string, any> | null;
    geo_source?: string | null;
    geocoded_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface AddressPayload extends Record<string, any> {
    label?: string | null;
    value?: string | null;
    unrestricted_value?: string | null;
    line1?: string | null;
    line2?: string | null;
    city?: string | null;
    postal_code?: string | null;
    flat?: string | null;
    entrance?: string | null;
    floor?: string | null;
    intercom?: string | null;
    lat?: number | null;
    lng?: number | null;
    raw_dadata_json?: Record<string, any> | null;
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

import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import {
    createRestaurantRequest,
    deleteRestaurantRequest,
    getRestaurantBySlug,
    listManagedRestaurants,
    listRestaurants,
    updateRestaurantRequest,
} from '~/domains/restaurants/api';
import type { PaginationMeta } from '~/utils/api/pagination';

export interface Restaurant {
    id: number;
    name: string;
    slug: string;
    phone: string | null;
    is_active: boolean;
    prep_time_min: number | null;
    prep_time_max: number | null;
    address_id: number | null;
    logo_media_id?: number | null;
    created_at: string;
    updated_at: string;
    address?: {
        id: number;
        line1: string;
        line2: string | null;
        city: string | null;
        postal_code: string | null;
        lat: string | null;
        lng: string | null;
    } | null;
    logo?: {
        id: number;
        url: string;
    } | null;
}

export interface RestaurantPayload {
    name: string;
    slug?: string | null;
    phone?: string | null;
    is_active?: boolean;
    prep_time_min?: number | null;
    prep_time_max?: number | null;
    logo_media_id?: number | null;
    address?: {
        label?: string | null;
        line1: string;
        line2?: string | null;
        city?: string | null;
        postal_code?: string | null;
        lat?: number | null;
        lng?: number | null;
    } | null;
    owner_id?: number | null;
}

export function useRestaurants() {
    const { handleApiError, errorMessage } = useApiHelpers();

    const items = ref<Restaurant[]>([]);
    const pagination = ref<PaginationMeta | null>(null);
    const loading = ref(false);

    const fetchRestaurants = async (params: Record<string, any> = {}) => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const response = await listRestaurants(params);
            items.value = response.items;
            pagination.value = response.pagination;
            return response;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const fetchMyRestaurants = async () => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const managedRestaurants = await listManagedRestaurants();
            items.value = managedRestaurants;
            pagination.value = null;

            return managedRestaurants;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const fetchRestaurant = async (slug: string): Promise<Restaurant> => {
        errorMessage.value = null;

        try {
            return await getRestaurantBySlug(slug);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const createRestaurant = async (payload: RestaurantPayload): Promise<Restaurant> => {
        errorMessage.value = null;

        try {
            return await createRestaurantRequest(payload);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const updateRestaurant = async (
        id: number,
        payload: Partial<RestaurantPayload>,
    ): Promise<Restaurant> => {
        errorMessage.value = null;

        try {
            return await updateRestaurantRequest(id, payload);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const deleteRestaurant = async (id: number): Promise<void> => {
        errorMessage.value = null;

        try {
            await deleteRestaurantRequest(id);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    return {
        // state
        items,
        pagination,
        loading,
        errorMessage,
        // methods
        fetchRestaurants,
        fetchMyRestaurants,
        fetchRestaurant,
        createRestaurant,
        updateRestaurant,
        deleteRestaurant,
    };
}

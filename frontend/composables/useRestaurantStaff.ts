// composables/useRestaurantStaff.ts
import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import {
    acceptRestaurantStaffInvite,
    addRestaurantStaff,
    createRestaurantStaffInvite,
    getRestaurantStaffInvite,
    listRestaurantStaff,
    removeRestaurantStaff,
    updateRestaurantStaff,
} from '~/domains/restaurants/manage/api';

export type RestaurantRole = 'OWNER' | 'MANAGER' | 'STAFF';

export interface RestaurantStaffMember {
    id: number;
    email: string;
    phone: string | null;
    name: string | null;
    role: RestaurantRole;
}

export interface RestaurantStaffPayload {
    user_id: number;
    role: RestaurantRole;
}

export interface RestaurantStaffUpdatePayload {
    role: RestaurantRole;
}

export interface RestaurantStaffInviteActor {
    id: number;
    name: string | null;
    email: string;
}

export interface RestaurantStaffInvite {
    token: string;
    role: RestaurantRole;
    expires_at: string;
    accepted_at: string | null;
    restaurant: {
        id: number;
        name: string;
        slug: string;
    };
    invited_by: RestaurantStaffInviteActor | null;
    accepted_by?: RestaurantStaffInviteActor | null;
}

export interface RestaurantStaffInvitePayload {
    role: Exclude<RestaurantRole, 'OWNER'>;
    expires_in_minutes?: number;
}

export function useRestaurantStaff() {
    const { handleApiError, errorMessage } = useApiHelpers();

    const items = ref<RestaurantStaffMember[]>([]);
    const loading = ref(false);
    const invitesLoading = ref(false);

    const fetchStaff = async (restaurantSlug: string) => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const staff = await listRestaurantStaff(restaurantSlug);
            items.value = staff;
            return staff;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const addStaff = async (
        restaurantSlug: string,
        payload: RestaurantStaffPayload,
    ): Promise<void> => {
        errorMessage.value = null;

        try {
            await addRestaurantStaff(
                restaurantSlug,
                payload,
            );
            // после успешного добавления просто перезагрузим список
            await fetchStaff(restaurantSlug);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const updateStaff = async (
        restaurantSlug: string,
        userId: number,
        payload: RestaurantStaffUpdatePayload,
    ): Promise<void> => {
        errorMessage.value = null;

        try {
            await updateRestaurantStaff(
                restaurantSlug,
                userId,
                payload,
            );
            // локально обновим роль
            items.value = items.value.map((m) =>
                m.id === userId ? { ...m, role: payload.role } : m,
            );
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const removeStaff = async (restaurantSlug: string, userId: number) => {
        errorMessage.value = null;

        try {
            await removeRestaurantStaff(restaurantSlug, userId);
            items.value = items.value.filter((m) => m.id !== userId);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const createInvite = async (
        restaurantSlug: string,
        payload: RestaurantStaffInvitePayload,
    ): Promise<RestaurantStaffInvite> => {
        invitesLoading.value = true;
        errorMessage.value = null;

        try {
            return await createRestaurantStaffInvite(restaurantSlug, payload);
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            invitesLoading.value = false;
        }
    };

    const fetchInvite = async (token: string): Promise<RestaurantStaffInvite> => {
        invitesLoading.value = true;
        errorMessage.value = null;

        try {
            return await getRestaurantStaffInvite(token);
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            invitesLoading.value = false;
        }
    };

    const acceptInvite = async (token: string): Promise<RestaurantStaffInvite> => {
        invitesLoading.value = true;
        errorMessage.value = null;

        try {
            return await acceptRestaurantStaffInvite(token);
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            invitesLoading.value = false;
        }
    };

    return {
        items,
        loading,
        invitesLoading,
        errorMessage,
        fetchStaff,
        addStaff,
        updateStaff,
        removeStaff,
        createInvite,
        fetchInvite,
        acceptInvite,
    };
}

import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import {
    getAdminDashboard,
    listAdminCouriers,
    listAdminOrders,
    listAdminRestaurants,
    listAdminUsers,
} from '~/domains/admin/api';
import type { PaginationMeta } from '~/utils/api/pagination';

export function useAdminList<T>(loader: (params?: Record<string, any>) => Promise<{ items: T[]; pagination: PaginationMeta }>) {
    const { handleApiError, errorMessage } = useApiHelpers();
    const items = ref<T[]>([]);
    const pagination = ref<PaginationMeta | null>(null);
    const loading = ref(false);

    const fetchItems = async (params: Record<string, any> = {}) => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const response = await loader(params);
            items.value = response.items as any;
            pagination.value = response.pagination;
            return response;
        } catch (error) {
            handleApiError(error);
            throw error;
        } finally {
            loading.value = false;
        }
    };

    return {
        items,
        pagination,
        loading,
        errorMessage,
        fetchItems,
    };
}

export const useAdminDashboard = () => {
    const { handleApiError, errorMessage } = useApiHelpers();
    const dashboard = ref<any | null>(null);
    const loading = ref(false);

    const fetchDashboard = async (params: Record<string, any> = {}) => {
        loading.value = true;
        errorMessage.value = null;

        try {
            dashboard.value = await getAdminDashboard(params);
            return dashboard.value;
        } catch (error) {
            handleApiError(error);
            throw error;
        } finally {
            loading.value = false;
        }
    };

    return { dashboard, loading, errorMessage, fetchDashboard };
};

export const useAdminUsers = () => useAdminList(listAdminUsers);
export const useAdminRestaurants = () => useAdminList(listAdminRestaurants);
export const useAdminCouriers = () => useAdminList(listAdminCouriers);
export const useAdminOrders = () => useAdminList(listAdminOrders);

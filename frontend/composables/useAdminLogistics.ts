import { computed, ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';

export interface LogisticsSetting {
    id: number;
    key: string;
    value: string | null;
    type: 'string' | 'integer' | 'decimal' | 'boolean' | 'json';
    group: string;
    label: string;
    description: string | null;
    validation_rules: string[] | null;
    sort_order: number;
    is_admin_editable: boolean;
}

export type LogisticsSettingGroups = Record<string, LogisticsSetting[]>;

export interface LogisticsDebugRoute {
    mode: string;
    distance_meters: number;
    duration_seconds: number;
    encoded_shape: string | null;
    request: Record<string, any>;
    raw_response: Record<string, any>;
    settings_snapshot: Record<string, any>;
}

export function useAdminLogistics() {
    const { $api } = useNuxtApp();
    const { handleApiError, errorMessage } = useApiHelpers();
    const groups = ref<LogisticsSettingGroups>({});
    const loading = ref(false);
    const saving = ref(false);
    const debugLoading = ref(false);
    const addressDebugResult = ref<any | null>(null);
    const routeDebugResult = ref<LogisticsDebugRoute | null>(null);
    const orderRoutesResult = ref<any | null>(null);

    const flatSettings = computed(() => Object.values(groups.value).flat());

    const fetchSettings = async () => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.get<{ groups: LogisticsSettingGroups }>('/admin/logistics/settings');
            groups.value = data.groups;
            return data.groups;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const saveSettings = async () => {
        saving.value = true;
        errorMessage.value = null;

        try {
            const settings = Object.fromEntries(
                flatSettings.value.map((setting) => [setting.key, setting.value]),
            );
            const { data } = await $api.put<{ groups: LogisticsSettingGroups }>('/admin/logistics/settings', {
                settings,
            });
            groups.value = data.groups;
            return data.groups;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            saving.value = false;
        }
    };

    const testAddress = async (address: string) => {
        debugLoading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.post<{ address: any }>('/admin/logistics/test-address', { address });
            addressDebugResult.value = data.address;
            return data.address;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            debugLoading.value = false;
        }
    };

    const testRoute = async (payload: Record<string, any>) => {
        debugLoading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.post<{ route: LogisticsDebugRoute }>('/admin/logistics/test-route', payload);
            routeDebugResult.value = data.route;
            return data.route;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            debugLoading.value = false;
        }
    };

    const fetchOrderRoutes = async (orderId: number) => {
        debugLoading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.get(`/admin/orders/${orderId}/routes`);
            orderRoutesResult.value = data;
            return data;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            debugLoading.value = false;
        }
    };

    return {
        groups,
        flatSettings,
        loading,
        saving,
        debugLoading,
        errorMessage,
        addressDebugResult,
        routeDebugResult,
        orderRoutesResult,
        fetchSettings,
        saveSettings,
        testAddress,
        testRoute,
        fetchOrderRoutes,
    };
}

import type {
    CourierOrder,
    CourierProfile,
    CourierShift,
} from '~/domains/courier/types';

export interface CourierOrdersSnapshot {
    available: CourierOrder[];
    active: CourierOrder[];
    history: CourierOrder[];
    ordersBlockedByShift: boolean;
}

const normalizeOrders = (raw: any): CourierOrder[] => {
    if (Array.isArray(raw)) return raw as CourierOrder[];
    if (Array.isArray(raw?.data)) return raw.data as CourierOrder[];
    if (Array.isArray(raw?.orders)) return raw.orders as CourierOrder[];
    return [];
};

const normalizeCourierProfile = (raw: any): CourierProfile => {
    return {
        ...raw,
        rating: raw?.rating != null ? Number(raw.rating) : null,
    };
};

const extractShiftFromResponse = (raw: any): CourierShift | null => {
    if (raw?.shift !== undefined) return raw.shift as CourierShift | null;
    if (raw?.data?.shift !== undefined) {
        return raw.data.shift as CourierShift | null;
    }
    if (raw?.data) return raw.data as CourierShift | null;
    return raw as CourierShift | null;
};

const extractOrderFromResponse = (raw: any): CourierOrder => {
    if (raw?.data) return raw.data as CourierOrder;
    if (raw?.order) return raw.order as CourierOrder;
    return raw as CourierOrder;
};

const isNoShiftError = (error: any): boolean => {
    const message = error?.response?.data?.message;

    return (
        error?.response?.status === 422 &&
        typeof message === 'string' &&
        message.toLowerCase().includes('нет открытой смены')
    );
};

export const getCourierProfile = async (): Promise<CourierProfile> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<any>('/courier/profile');
    const raw = (data as any).profile ?? data;

    return normalizeCourierProfile(raw);
};

export const getCurrentCourierShift = async (): Promise<CourierShift | null> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ shift: CourierShift | null }>(
        '/courier/shifts/current',
    );

    return extractShiftFromResponse(data);
};

export const startCourierShift = async (): Promise<CourierShift | null> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<any>('/courier/shifts/start');

    return extractShiftFromResponse(data);
};

export const endCourierShift = async (): Promise<CourierShift | null> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<any>('/courier/shifts/end');

    return extractShiftFromResponse(data);
};

export const listCourierOrders = async (): Promise<CourierOrdersSnapshot> => {
    const { $api } = useNuxtApp();
    const historyResponse = await $api.get<any>('/courier/orders/history');
    const history = normalizeOrders(historyResponse.data);

    try {
        const [availableResponse, activeResponse] = await Promise.all([
            $api.get<any>('/courier/orders/available'),
            $api.get<any>('/courier/orders/active'),
        ]);

        return {
            available: normalizeOrders(availableResponse.data),
            active: normalizeOrders(activeResponse.data),
            history,
            ordersBlockedByShift: false,
        };
    } catch (error: any) {
        if (isNoShiftError(error)) {
            return {
                available: [],
                active: [],
                history,
                ordersBlockedByShift: true,
            };
        }

        throw error;
    }
};

export const assignCourierOrder = async (
    orderId: number,
): Promise<CourierOrder> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<any>(`/courier/orders/${orderId}/assign`);

    return extractOrderFromResponse(data);
};

export const markCourierOrderPickedUp = async (
    orderId: number,
): Promise<CourierOrder> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<any>(
        `/courier/orders/${orderId}/picked-up`,
    );

    return extractOrderFromResponse(data);
};

export const markCourierOrderDelivered = async (
    orderId: number,
): Promise<CourierOrder> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<any>(
        `/courier/orders/${orderId}/delivered`,
    );

    return extractOrderFromResponse(data);
};

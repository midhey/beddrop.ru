import type { Order } from '~/composables/useOrders';
import type { Restaurant } from '~/composables/useRestaurants';
import type { LaravelPaginated } from '~/utils/api/pagination';
import { mapLaravelPagination } from '~/utils/api/pagination';

export interface AdminUser {
    id: number;
    email: string;
    phone: string;
    name: string | null;
    is_admin: boolean;
    is_banned: boolean;
    orders_count?: number;
    restaurants_count?: number;
    courier_profile?: any | null;
    created_at: string;
    updated_at: string;
}

export interface AdminCourier {
    user_id: number;
    status: string;
    vehicle: string | null;
    rating: number | null;
    user?: AdminUser;
    orders_count?: number;
    latest_location?: any | null;
    open_shift?: any | null;
    created_at: string;
    updated_at: string;
}

const normalizePaginated = <T>(raw: any) => {
    if (Array.isArray(raw?.data)) {
        return mapLaravelPagination(raw as LaravelPaginated<T>);
    }

    if (Array.isArray(raw?.data?.data)) {
        return mapLaravelPagination(raw.data as LaravelPaginated<T>);
    }

    if (Array.isArray(raw?.items)) {
        return {
            items: raw.items as T[],
            pagination: raw.pagination ?? {
                current_page: 1,
                last_page: 1,
                per_page: raw.items.length,
                total: raw.items.length,
            },
        };
    }

    return {
        items: [] as T[],
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 0,
            total: 0,
        },
    };
};

export const getAdminDashboard = async (params: Record<string, any> = {}) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get('/admin/dashboard', { params });
    return data;
};

export const listAdminUsers = async (params: Record<string, any> = {}) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<LaravelPaginated<AdminUser>>('/admin/users', { params });
    return normalizePaginated<AdminUser>(data);
};

export const getAdminUser = async (id: number) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ user: AdminUser }>(`/admin/users/${id}`);
    return data.user;
};

export const updateAdminUser = async (id: number, payload: Partial<Pick<AdminUser, 'is_admin' | 'is_banned'>>) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.patch<{ user: AdminUser }>(`/admin/users/${id}`, payload);
    return data.user;
};

export const listAdminRestaurants = async (params: Record<string, any> = {}) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<LaravelPaginated<Restaurant>>('/admin/restaurants', { params });
    return normalizePaginated<Restaurant>(data);
};

export const getAdminRestaurant = async (id: number) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get(`/admin/restaurants/${id}`);
    return data;
};

export const updateAdminRestaurant = async (id: number, payload: Record<string, any>) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.put(`/admin/restaurants/${id}`, payload);
    return data.restaurant as Restaurant;
};

export const listAdminCouriers = async (params: Record<string, any> = {}) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<LaravelPaginated<AdminCourier>>('/admin/couriers', { params });
    return normalizePaginated<AdminCourier>(data);
};

export const getAdminCourier = async (userId: number) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get(`/admin/couriers/${userId}`);
    return data;
};

export const updateAdminCourier = async (userId: number, payload: Record<string, any>) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.patch(`/admin/couriers/${userId}`, payload);
    return data.courier as AdminCourier;
};

export const createAdminCourier = async (payload: Record<string, any>) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post('/admin/couriers', payload);
    return data.courier as AdminCourier;
};

export const listAdminOrders = async (params: Record<string, any> = {}) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<LaravelPaginated<Order>>('/admin/orders', { params });
    return normalizePaginated<Order>(data);
};

export const getAdminOrder = async (id: number) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ data: Order }>(`/admin/orders/${id}`);
    return data.data;
};

const postOrderAction = async (id: number, action: string, payload: Record<string, any> = {}) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ data: Order }>(`/admin/orders/${id}/${action}`, payload);
    return data.data;
};

export const adminAcceptOrder = (id: number) => postOrderAction(id, 'accept');
export const adminMarkReady = (id: number) => postOrderAction(id, 'ready');
export const adminCancelOrder = (id: number, reason: string | null) => postOrderAction(id, 'cancel', { reason });
export const adminAssignCourier = (id: number, courierUserId: number) => postOrderAction(id, 'assign-courier', { courier_user_id: courierUserId });
export const adminUnassignCourier = (id: number) => postOrderAction(id, 'unassign-courier');
export const adminMarkPickedUp = (id: number) => postOrderAction(id, 'picked-up');
export const adminMarkDelivered = (id: number) => postOrderAction(id, 'delivered');

export const adminUpdatePayment = async (id: number, paymentStatus: string) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.patch<{ data: Order }>(`/admin/orders/${id}/payment`, {
        payment_status: paymentStatus,
    });
    return data.data;
};

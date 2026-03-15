import type {
    Product,
    ProductImageRef,
    ProductPayload,
} from '~/composables/useRestaurantProducts';
import type {
    RestaurantStaffInvite,
    RestaurantStaffInvitePayload,
    RestaurantStaffMember,
} from '~/composables/useRestaurantStaff';
import type { Order } from '~/composables/useOrders';
import type { LaravelPaginated } from '~/utils/api/pagination';
import { mapLaravelPagination } from '~/utils/api/pagination';

export const listRestaurantOrders = async (
    restaurantSlug: string,
    params: Record<string, any> = {},
) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<LaravelPaginated<Order>>(
        `/restaurants/${restaurantSlug}/orders`,
        { params },
    );

    return mapLaravelPagination(data);
};

export const acceptRestaurantOrder = async (
    restaurantSlug: string,
    orderId: number,
): Promise<Order> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ data: Order }>(
        `/restaurants/${restaurantSlug}/orders/${orderId}/accept`,
    );

    return data.data;
};

export const cancelRestaurantOrder = async (
    restaurantSlug: string,
    orderId: number,
    reason?: string,
): Promise<Order> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ data: Order }>(
        `/restaurants/${restaurantSlug}/orders/${orderId}/cancel`,
        reason ? { reason } : {},
    );

    return data.data;
};

export const listRestaurantProducts = async (
    restaurantSlug: string,
    params: Record<string, any> = {},
) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<LaravelPaginated<Product>>(
        `/restaurants/${restaurantSlug}/products`,
        { params },
    );

    return mapLaravelPagination(data);
};

export const getRestaurantProduct = async (
    restaurantSlug: string,
    productId: number,
): Promise<Product> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ data: Product }>(
        `/restaurants/${restaurantSlug}/products/${productId}`,
    );

    return data.data;
};

export const createRestaurantProduct = async (
    restaurantSlug: string,
    payload: ProductPayload,
): Promise<Product> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ data: Product }>(
        `/restaurants/${restaurantSlug}/products`,
        payload,
    );

    return data.data;
};

export const updateRestaurantProduct = async (
    restaurantSlug: string,
    productId: number,
    payload: Partial<ProductPayload>,
): Promise<Product> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.put<{ data: Product }>(
        `/restaurants/${restaurantSlug}/products/${productId}`,
        payload,
    );

    return data.data;
};

export const deleteRestaurantProduct = async (
    restaurantSlug: string,
    productId: number,
): Promise<void> => {
    const { $api } = useNuxtApp();
    await $api.delete(
        `/restaurants/${restaurantSlug}/products/${productId}`,
    );
};

interface ProductImagePayload {
    media_id: number;
    sort_order?: number;
    is_cover?: boolean;
}

export const addRestaurantProductImage = async (
    restaurantSlug: string,
    productId: number,
    payload: ProductImagePayload,
): Promise<ProductImageRef> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ image: ProductImageRef }>(
        `/restaurants/${restaurantSlug}/products/${productId}/images`,
        payload,
    );

    return data.image;
};

export const updateRestaurantProductImage = async (
    restaurantSlug: string,
    productId: number,
    imageId: number,
    payload: Partial<ProductImagePayload>,
): Promise<ProductImageRef> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.put<{ image: ProductImageRef }>(
        `/restaurants/${restaurantSlug}/products/${productId}/images/${imageId}`,
        payload,
    );

    return data.image;
};

export const deleteRestaurantProductImage = async (
    restaurantSlug: string,
    productId: number,
    imageId: number,
): Promise<void> => {
    const { $api } = useNuxtApp();
    await $api.delete(
        `/restaurants/${restaurantSlug}/products/${productId}/images/${imageId}`,
    );
};

export const listRestaurantStaff = async (
    restaurantSlug: string,
): Promise<RestaurantStaffMember[]> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ staff: RestaurantStaffMember[] }>(
        `/restaurants/${restaurantSlug}/users`,
    );

    return data.staff;
};

export const addRestaurantStaff = async (
    restaurantSlug: string,
    payload: { user_id: number; role: string },
): Promise<void> => {
    const { $api } = useNuxtApp();
    await $api.post(
        `/restaurants/${restaurantSlug}/users`,
        payload,
    );
};

export const updateRestaurantStaff = async (
    restaurantSlug: string,
    userId: number,
    payload: { role: string },
): Promise<void> => {
    const { $api } = useNuxtApp();
    await $api.put(
        `/restaurants/${restaurantSlug}/users/${userId}`,
        payload,
    );
};

export const removeRestaurantStaff = async (
    restaurantSlug: string,
    userId: number,
): Promise<void> => {
    const { $api } = useNuxtApp();
    await $api.delete(
        `/restaurants/${restaurantSlug}/users/${userId}`,
    );
};

export const createRestaurantStaffInvite = async (
    restaurantSlug: string,
    payload: RestaurantStaffInvitePayload,
): Promise<RestaurantStaffInvite> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ invite: RestaurantStaffInvite }>(
        `/restaurants/${restaurantSlug}/staff-invites`,
        payload,
    );

    return data.invite;
};

export const getRestaurantStaffInvite = async (
    token: string,
): Promise<RestaurantStaffInvite> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ invite: RestaurantStaffInvite }>(
        `/staff-invites/${token}`,
    );

    return data.invite;
};

export const acceptRestaurantStaffInvite = async (
    token: string,
): Promise<RestaurantStaffInvite> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ invite: RestaurantStaffInvite }>(
        `/staff-invites/${token}/accept`,
    );

    return data.invite;
};

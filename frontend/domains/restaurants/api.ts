import type { Restaurant, RestaurantPayload } from '~/composables/useRestaurants';
import type { LaravelPaginated } from '~/utils/api/pagination';
import { mapLaravelPagination } from '~/utils/api/pagination';

export const listRestaurants = async (
    params: Record<string, any> = {},
) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<LaravelPaginated<Restaurant>>('/restaurants', {
        params,
    });

    return mapLaravelPagination(data);
};

export const listManagedRestaurants = async (): Promise<Restaurant[]> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ restaurants: Restaurant[] }>('/restaurants/my');
    return data.restaurants;
};

export const getRestaurantBySlug = async (slug: string): Promise<Restaurant> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ restaurant: Restaurant }>(
        `/restaurants/${slug}`,
    );

    return data.restaurant;
};

export const createRestaurantRequest = async (
    payload: RestaurantPayload,
): Promise<Restaurant> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ restaurant: Restaurant }>(
        '/restaurants',
        payload,
    );

    return data.restaurant;
};

export const updateRestaurantRequest = async (
    id: number,
    payload: Partial<RestaurantPayload>,
): Promise<Restaurant> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.put<{ restaurant: Restaurant }>(
        `/restaurants/${id}`,
        payload,
    );

    return data.restaurant;
};

export const deleteRestaurantRequest = async (id: number): Promise<void> => {
    const { $api } = useNuxtApp();
    await $api.delete(`/restaurants/${id}`);
};

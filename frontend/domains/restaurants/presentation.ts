import type { Restaurant } from '~/composables/useRestaurants';

type RestaurantAddressSource = Pick<Restaurant, 'address'>;
type RestaurantPrepTimeSource = Pick<Restaurant, 'prep_time_min' | 'prep_time_max'>;

export const formatRestaurantAddress = (
    restaurant: RestaurantAddressSource | null | undefined,
): string => {
    if (!restaurant?.address) return 'Адрес не указан';

    return [
        restaurant.address.city,
        restaurant.address.line1,
        restaurant.address.line2,
        restaurant.address.postal_code,
    ]
        .filter(Boolean)
        .join(', ');
};

export const formatRestaurantPrepTime = (
    restaurant: RestaurantPrepTimeSource | null | undefined,
): string | null => {
    if (!restaurant) return null;

    const min = restaurant.prep_time_min;
    const max = restaurant.prep_time_max;

    if (min && max) return `~${min}–${max} мин`;
    if (min && !max) return `от ${min} мин`;
    if (!min && max) return `до ${max} мин`;
    return null;
};

export const getRestaurantActivityLabel = (isActive: boolean): string => {
    return isActive ? 'Активен' : 'Выключен';
};

export const getRestaurantActivityStatus = (
    isActive: boolean,
): 'active' | 'inactive' => {
    return isActive ? 'active' : 'inactive';
};

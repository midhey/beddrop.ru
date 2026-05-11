import type { Restaurant } from '~/composables/useRestaurants';

type RestaurantAddressSource = Pick<Restaurant, 'address'>;
type RestaurantPrepTimeSource = Pick<Restaurant, 'prep_time_min' | 'prep_time_max'>;
type RestaurantAvailabilitySource = Pick<Restaurant, 'availability'>;

export const formatRestaurantAddress = (
    restaurant: RestaurantAddressSource | null | undefined,
): string => {
    if (!restaurant?.address) return 'Адрес не указан';

    return [
        restaurant.address.value || restaurant.address.unrestricted_value,
        restaurant.address.flat ? `кв. ${restaurant.address.flat}` : null,
        restaurant.address.entrance ? `подъезд ${restaurant.address.entrance}` : null,
        restaurant.address.floor ? `этаж ${restaurant.address.floor}` : null,
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

export const getRestaurantAvailabilityLabel = (
    restaurant: RestaurantAvailabilitySource | null | undefined,
): string => {
    const availability = restaurant?.availability;

    if (!availability) return 'Статус заказов неизвестен';
    if (availability.is_open) return 'Заказы принимаются';

    switch (availability.status) {
        case 'inactive':
            return 'Скрыт из каталога';
        case 'manually_closed':
            return availability.closed_reason
                ? `Пауза заказов: ${availability.closed_reason}`
                : 'Заказы на паузе';
        case 'closed_by_schedule':
            return 'Закрыт по графику';
        default:
            return 'Заказы недоступны';
    }
};

export const getRestaurantAvailabilityStatus = (
    restaurant: RestaurantAvailabilitySource | null | undefined,
): 'active' | 'inactive' => {
    return restaurant?.availability?.is_open ? 'active' : 'inactive';
};

export const formatRestaurantWorkingHours = (
    restaurant: RestaurantAvailabilitySource | null | undefined,
): string | null => {
    const availability = restaurant?.availability;

    if (!availability?.opens_at || !availability.closes_at) {
        return null;
    }

    return `${availability.opens_at}–${availability.closes_at}`;
};

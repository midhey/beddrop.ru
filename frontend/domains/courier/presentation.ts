import type {
    CourierAddress,
    CourierOrder,
    CourierShift,
    CourierVehicle,
} from '~/domains/courier/types';

const COURIER_PROFILE_STATUS_LABELS: Record<string, string> = {
    ACTIVE: 'Активен',
    SUSPENDED: 'Заблокирован',
};

const COURIER_VEHICLE_LABELS: Record<CourierVehicle, string> = {
    FOOT: 'Пешком',
    BIKE: 'Велосипед',
    SCOOTER: 'Самокат',
    CAR: 'Авто',
};

const COURIER_ORDER_STATUS_LABELS: Record<string, string> = {
    CREATED: 'Создан',
    ACCEPTED_BY_RESTAURANT: 'Принят рестораном',
    COURIER_ASSIGNED: 'Курьер назначен',
    PICKED_UP: 'В пути к клиенту',
    DELIVERED: 'Доставлен',
    CANCELED_BY_USER: 'Отменён клиентом',
    CANCELED_BY_RESTAURANT: 'Отменён рестораном',
};

export const getCourierProfileStatusLabel = (status: string): string => {
    return COURIER_PROFILE_STATUS_LABELS[status] || status;
};

export const getCourierVehicleLabel = (
    vehicle: CourierVehicle | string | null | undefined,
    fallback = 'Не указан',
): string => {
    if (!vehicle) return fallback;

    return COURIER_VEHICLE_LABELS[vehicle as CourierVehicle] || vehicle;
};

export const formatCourierRating = (
    rating: number | null | undefined,
    fallback = '—',
): string => {
    if (rating == null) return fallback;

    return rating.toFixed(2);
};

export const formatCourierAddress = (
    address: CourierAddress | null | undefined,
    fallback = '',
): string => {
    if (!address?.line1) return fallback;

    const parts = [];

    if (address.city) {
        parts.push(address.city);
    }

    parts.push(address.line1);

    if (address.line2) {
        parts.push(address.line2);
    }

    return parts.join(', ');
};

export const getCourierOrderStatusLabel = (status: string): string => {
    return COURIER_ORDER_STATUS_LABELS[status] || status;
};

export const getCourierRestaurantAddress = (
    order: Pick<CourierOrder, 'restaurant'>,
): string => {
    return formatCourierAddress(order.restaurant?.address ?? null);
};

export const getCourierDeliveryAddress = (
    order: Pick<CourierOrder, 'delivery_address'>,
): string => {
    return formatCourierAddress(order.delivery_address ?? null);
};

export const isCourierShiftOpen = (
    shift: CourierShift | null | undefined,
): boolean => {
    if (!shift) return false;
    if (shift.ended_at) return false;

    return shift.status === 'OPEN';
};

export const canCourierMarkPickedUp = (
    order: Pick<CourierOrder, 'status'>,
): boolean => {
    return order.status === 'COURIER_ASSIGNED';
};

export const canCourierMarkDelivered = (
    order: Pick<CourierOrder, 'status'>,
): boolean => {
    return order.status === 'PICKED_UP';
};

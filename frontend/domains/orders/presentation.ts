import type { Order, OrderEvent } from '~/composables/useOrders';

export type OrderStatusLabelVariant = 'customer' | 'restaurant' | 'banner';

const ORDER_STATUS_LABELS: Record<OrderStatusLabelVariant, Record<string, string>> = {
    customer: {
        CREATED: 'Создан',
        ACCEPTED_BY_RESTAURANT: 'Принят рестораном',
        COURIER_ASSIGNED: 'Курьер назначен',
        PICKED_UP: 'У курьера',
        DELIVERED: 'Доставлен',
        CANCELED_BY_USER: 'Отменён пользователем',
        CANCELED_BY_RESTAURANT: 'Отменён рестораном',
    },
    restaurant: {
        CREATED: 'Новый',
        ACCEPTED_BY_RESTAURANT: 'Принят рестораном',
        COURIER_ASSIGNED: 'Курьер назначен',
        PICKED_UP: 'У курьера',
        DELIVERED: 'Доставлен',
        CANCELED_BY_USER: 'Отменён пользователем',
        CANCELED_BY_RESTAURANT: 'Отменён рестораном',
    },
    banner: {
        CREATED: 'Заказ создан',
        ACCEPTED_BY_RESTAURANT: 'Ресторан принял заказ',
        COURIER_ASSIGNED: 'Курьер назначен',
        PICKED_UP: 'Заказ у курьера',
        DELIVERED: 'Заказ доставлен',
        CANCELED_BY_USER: 'Заказ отменён',
        CANCELED_BY_RESTAURANT: 'Заказ отменён рестораном',
    },
};

const ORDER_STATUS_CLASS_MAP: Record<string, string> = {
    CREATED: 'order-status--info',
    ACCEPTED_BY_RESTAURANT: 'order-status--info',
    COURIER_ASSIGNED: 'order-status--info',
    PICKED_UP: 'order-status--info',
    DELIVERED: 'order-status--success',
    CANCELED_BY_USER: 'order-status--danger',
    CANCELED_BY_RESTAURANT: 'order-status--danger',
};

const BANNER_STATUS_CLASS_MAP: Record<string, string> = {
    CREATED: 'active-order-banner__status--info',
    ACCEPTED_BY_RESTAURANT: 'active-order-banner__status--info',
    COURIER_ASSIGNED: 'active-order-banner__status--info',
    PICKED_UP: 'active-order-banner__status--info',
    DELIVERED: 'active-order-banner__status--success',
    CANCELED_BY_USER: 'active-order-banner__status--danger',
    CANCELED_BY_RESTAURANT: 'active-order-banner__status--danger',
};

const PAYMENT_STATUS_LABELS: Record<string, string> = {
    PENDING: 'Ожидает оплаты',
    AUTHORIZED: 'Оплата авторизована',
    PAID: 'Оплачен',
    REFUNDED: 'Возврат',
    FAILED: 'Ошибка оплаты',
};

const PAYMENT_METHOD_LABELS: Record<string, string> = {
    CASH: 'Наличными',
    CARD: 'Картой курьеру',
    ONLINE: 'Онлайн',
};

export const FINAL_ORDER_STATUSES = [
    'DELIVERED',
    'CANCELED_BY_USER',
    'CANCELED_BY_RESTAURANT',
];

export const getOrderStatusLabel = (
    status: string,
    variant: OrderStatusLabelVariant = 'customer',
): string => {
    return ORDER_STATUS_LABELS[variant][status] || status;
};

export const getOrderStatusClass = (status: string): string => {
    return ORDER_STATUS_CLASS_MAP[status] || '';
};

export const getActiveOrderBannerStatusClass = (status: string): string => {
    return BANNER_STATUS_CLASS_MAP[status] || '';
};

export const getPaymentStatusLabel = (status: string): string => {
    return PAYMENT_STATUS_LABELS[status] || status;
};

export const getPaymentMethodLabel = (method: string): string => {
    return PAYMENT_METHOD_LABELS[method] || method;
};

export const isFinalOrderStatus = (status: string): boolean => {
    return FINAL_ORDER_STATUSES.includes(status);
};

export const canRestaurantAcceptOrder = (
    order: Pick<Order, 'status'>,
): boolean => {
    return order.status === 'CREATED';
};

export const canRestaurantCancelOrder = (
    order: Pick<Order, 'status'>,
): boolean => {
    return !isFinalOrderStatus(order.status);
};

export const sortOrderEvents = <T extends Pick<OrderEvent, 'created_at'>>(
    events: T[] | null | undefined,
): T[] => {
    if (!events?.length) return [];

    return [...events].sort(
        (left, right) =>
            new Date(left.created_at).getTime() -
            new Date(right.created_at).getTime(),
    );
};

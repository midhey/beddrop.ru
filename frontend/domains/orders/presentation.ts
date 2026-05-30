import type { Order, OrderEvent } from '~/composables/useOrders';

export type OrderStatusLabelVariant = 'customer' | 'restaurant' | 'banner';

const ORDER_STATUS_LABELS: Record<OrderStatusLabelVariant, Record<string, string>> = {
    customer: {
        CREATED: 'Создан',
        ACCEPTED_BY_RESTAURANT: 'Принят рестораном',
        READY_FOR_PICKUP: 'Готов к выдаче',
        COURIER_ASSIGNED: 'Курьер назначен',
        PICKED_UP: 'У курьера',
        DELIVERED: 'Доставлен',
        CANCELED_BY_USER: 'Отменён пользователем',
        CANCELED_BY_RESTAURANT: 'Отменён рестораном',
        PAYMENT_PAID: 'Оплачен',
    },
    restaurant: {
        CREATED: 'Новый',
        ACCEPTED_BY_RESTAURANT: 'Принят рестораном',
        READY_FOR_PICKUP: 'Готов к выдаче',
        COURIER_ASSIGNED: 'Курьер назначен',
        PICKED_UP: 'У курьера',
        DELIVERED: 'Доставлен',
        CANCELED_BY_USER: 'Отменён пользователем',
        CANCELED_BY_RESTAURANT: 'Отменён рестораном',
        PAYMENT_PAID: 'Оплачен',
    },
    banner: {
        CREATED: 'Заказ создан',
        ACCEPTED_BY_RESTAURANT: 'Ресторан принял заказ',
        READY_FOR_PICKUP: 'Заказ готов к выдаче',
        COURIER_ASSIGNED: 'Курьер назначен',
        PICKED_UP: 'Заказ у курьера',
        DELIVERED: 'Заказ доставлен',
        CANCELED_BY_USER: 'Заказ отменён',
        CANCELED_BY_RESTAURANT: 'Заказ отменён рестораном',
        PAYMENT_PAID: 'Заказ оплачен',
    },
};

const ORDER_STATUS_CLASS_MAP: Record<string, string> = {
    CREATED: 'order-status--info',
    ACCEPTED_BY_RESTAURANT: 'order-status--info',
    READY_FOR_PICKUP: 'order-status--info',
    COURIER_ASSIGNED: 'order-status--info',
    PICKED_UP: 'order-status--info',
    DELIVERED: 'order-status--success',
    CANCELED_BY_USER: 'order-status--danger',
    CANCELED_BY_RESTAURANT: 'order-status--danger',
};

const BANNER_STATUS_CLASS_MAP: Record<string, string> = {
    CREATED: 'active-order-banner__status--info',
    ACCEPTED_BY_RESTAURANT: 'active-order-banner__status--info',
    READY_FOR_PICKUP: 'active-order-banner__status--info',
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
    order: Pick<Order, 'status' | 'payment_status'>,
): boolean => {
    return order.status === 'CREATED' && order.payment_status === 'PAID';
};

export const canRestaurantMarkReadyOrder = (
    order: Pick<Order, 'status'>,
): boolean => {
    return order.status === 'ACCEPTED_BY_RESTAURANT';
};

export const canRestaurantCancelOrder = (
    order: Pick<Order, 'status'>,
): boolean => {
    return !isFinalOrderStatus(order.status);
};

export const getRestaurantOrderNextStep = (
    order: Pick<Order, 'status' | 'courier_id'>,
): string => {
    if (order.status === 'CREATED') {
        return 'Проверьте состав и примите заказ в работу.';
    }

    if (order.status === 'ACCEPTED_BY_RESTAURANT') {
        return 'Готовьте заказ. Когда он будет собран, отметьте готовность к выдаче.';
    }

    if (order.status === 'READY_FOR_PICKUP') {
        return 'Заказ доступен курьерам. Ожидайте назначения курьера.';
    }

    if (order.status === 'COURIER_ASSIGNED') {
        return 'Курьер назначен и едет в ресторан. Подготовьте выдачу заказа.';
    }

    if (order.status === 'PICKED_UP') {
        return 'Заказ у курьера. Следующий шаг выполняется курьером после доставки клиенту.';
    }

    if (order.status === 'DELIVERED') {
        return 'Заказ доставлен и закрыт.';
    }

    if (order.status === 'CANCELED_BY_USER') {
        return 'Заказ отменён клиентом.';
    }

    if (order.status === 'CANCELED_BY_RESTAURANT') {
        return 'Заказ отменён рестораном.';
    }

    return order.courier_id ? 'Заказ в работе у курьера.' : 'Следите за сменой статуса заказа.';
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

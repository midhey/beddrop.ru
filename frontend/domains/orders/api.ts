import type {
    CreateOrderPayload,
    Order,
} from '~/composables/useOrders';
import { FINAL_ORDER_STATUSES } from '~/domains/orders/presentation';
import type { LaravelPaginated } from '~/utils/api/pagination';
import { mapLaravelPagination } from '~/utils/api/pagination';

export const listOrders = async (
    params: Record<string, any> = {},
) => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<LaravelPaginated<Order>>('/orders', {
        params,
    });

    return mapLaravelPagination(data);
};

export const getOrderById = async (id: number): Promise<Order> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.get<{ data: Order }>(`/orders/${id}`);

    return data.data;
};

export const createOrderRequest = async (
    payload: CreateOrderPayload,
): Promise<Order> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<{ data: Order }>('/orders', payload);

    return data.data;
};

export interface OrderPaymentResponse {
    payment: {
        id: number;
        provider: string;
        provider_payment_id: string | null;
        provider_status: string | null;
        confirmation_url: string | null;
        amount_value: string;
        currency: string;
        synced_at: string | null;
        confirmed_at: string | null;
        failed_at: string | null;
    };
    order: {
        id: number;
        status: string;
        payment_status: string;
    };
}

export interface OrderPaymentStatusResponse extends Partial<OrderPaymentResponse> {
    result: 'missing' | 'synced';
    message: string;
    order: {
        id: number;
        status: string;
        payment_status: string;
    };
}

export const initiateOrderPaymentRequest = async (
    id: number,
): Promise<OrderPaymentResponse> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<OrderPaymentResponse>(`/orders/${id}/payment`);

    return data;
};

export const syncOrderPaymentRequest = async (
    id: number,
): Promise<OrderPaymentResponse> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<OrderPaymentResponse>(`/orders/${id}/payment/sync`);

    return data;
};

export const checkOrderPaymentStatusRequest = async (
    id: number,
): Promise<OrderPaymentStatusResponse> => {
    const { $api } = useNuxtApp();
    const { data } = await $api.post<OrderPaymentStatusResponse>(`/orders/${id}/payment/status`);

    return data;
};

export const getActiveOrder = async (): Promise<Order | null> => {
    const response = await listOrders({ per_page: 20 });

    return (
        response.items.find(
            (order) => !FINAL_ORDER_STATUSES.includes(order.status),
        ) || null
    );
};

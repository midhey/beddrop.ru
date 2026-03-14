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

export const getActiveOrder = async (): Promise<Order | null> => {
    const response = await listOrders({ per_page: 20 });

    return (
        response.items.find(
            (order) => !FINAL_ORDER_STATUSES.includes(order.status),
        ) || null
    );
};

import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import {
    createOrderRequest,
    getOrderById,
    listOrders,
} from '~/domains/orders/api';
import type { Restaurant } from '~/composables/useRestaurants';
import type { Product } from '~/composables/useRestaurantProducts';
import type { PaginationMeta } from '~/utils/api/pagination';

export interface OrderItem {
    id: number;
    product_id: number;
    name_snapshot: string;
    unit_price_snapshot: string; // decimal:2
    quantity: number;
    subtotal: number;
    product?: Product | null;
}

export interface OrderEvent {
    id: number;
    event: string;
    payload: any;
    created_at: string;
}

export interface Order {
    id: number;
    status: string;
    payment_status: string;
    payment_method: 'CASH' | 'CARD' | 'ONLINE';
    total_price: string;
    courier_fee?: string | null;
    comment: string | null;
    delivery_address_id: number | null;
    delivery_lat: number | null;
    delivery_lng: number | null;

    restaurant?: Restaurant | null;
    items?: OrderItem[];
    events?: OrderEvent[];

    items_count?: number;
    calculated_total?: number;

    created_at: string;
    updated_at: string;
}

export interface CreateOrderPayload {
    payment_method: 'CASH' | 'CARD' | 'ONLINE';
    comment?: string | null;
    delivery_address_id?: number | null;
}

export function useOrders() {
    const { handleApiError, errorMessage } = useApiHelpers();

    const items = ref<Order[]>([]);
    const pagination = ref<PaginationMeta | null>(null);

    // загрузка списка / создания и т.п.
    const loading = ref(false);

    // текущий заказ для страницы /orders/[id]
    const current = ref<Order | null>(null);
    const currentLoading = ref(false);

    const fetchOrders = async (params: Record<string, any> = {}) => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const response = await listOrders(params);
            items.value = response.items;
            pagination.value = response.pagination;
            return response;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const fetchOrder = async (id: number): Promise<Order> => {
        currentLoading.value = true;
        errorMessage.value = null;

        try {
            const order = await getOrderById(id);
            current.value = order;
            return order;
        } catch (e) {
            current.value = null;
            handleApiError(e);
            throw e;
        } finally {
            currentLoading.value = false;
        }
    };

    const createOrder = async (
        payload: CreateOrderPayload,
    ): Promise<Order> => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const order = await createOrderRequest(payload);

            // добавим новый заказ в начало списка, если он уже загружен
            items.value = [order, ...items.value];

            return order;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    return {
        // список
        items,
        pagination,
        loading,

        // текущий заказ
        current,
        currentLoading,

        // общая ошибка
        errorMessage,

        // методы
        fetchOrders,
        fetchOrder,
        createOrder,
    };
}

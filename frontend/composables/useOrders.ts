import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import {
    createOrderRequest,
    getOrderById,
    initiateOrderPaymentRequest,
    listOrders,
    syncOrderPaymentRequest,
    cancelOrderRequest,
} from '~/domains/orders/api';
import type { Restaurant } from '~/composables/useRestaurants';
import type { Address } from '~/composables/useAddresses';
import type { Product } from '~/composables/useRestaurantProducts';
import type { PaginationMeta } from '~/utils/api/pagination';

export interface OrderUser {
    id: number;
    email: string;
    phone?: string | null;
    name?: string | null;
}

export interface OrderCourier {
    user_id?: number;
    user?: OrderUser | null;
}

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

export interface OrderRouteSegment {
    id: number;
    order_id: number;
    segment_type: 'restaurant_to_client' | 'courier_to_restaurant' | string;
    mode: string;
    distance_meters: number;
    duration_seconds: number;
    encoded_shape: string | null;
    raw_response?: Record<string, any> | null;
    settings_snapshot?: Record<string, any> | null;
    created_at?: string;
    updated_at?: string;
}

export interface OrderLogisticsSnapshot {
    price?: {
        base?: number;
        distance?: number;
        service?: number;
        total?: number;
    };
    time?: {
        prep?: number;
        pickup_buffer?: number;
        delivery?: number;
        buffer?: number;
        total?: number;
    };
    settings?: Record<string, any>;
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
    user_id?: number | null;
    courier_id?: number | null;
    delivery_lat: number | null;
    delivery_lng: number | null;
    delivery_distance_meters?: number | null;
    delivery_duration_seconds?: number | null;
    delivery_price_snapshot?: string | null;
    estimated_pickup_at?: string | null;
    estimated_delivery_at?: string | null;
    logistics_snapshot?: OrderLogisticsSnapshot | null;
    courier_approach_distance_meters?: number | null;
    delivery_address?: Address | null;
    user?: OrderUser | null;
    courier?: OrderCourier | null;

    restaurant?: Restaurant | null;
    items?: OrderItem[];
    events?: OrderEvent[];
    route_segments?: OrderRouteSegment[];

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

    const initiatePayment = async (id: number) => {
        errorMessage.value = null;

        try {
            return await initiateOrderPaymentRequest(id);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const syncPayment = async (id: number, options: { notify?: boolean } = {}) => {
        errorMessage.value = null;

        try {
            const response = await syncOrderPaymentRequest(id);
            if (current.value?.id === id) {
                current.value = {
                    ...current.value,
                    status: response.order.status,
                    payment_status: response.order.payment_status,
                };
            }

            return response;
        } catch (e) {
            handleApiError(e, options.notify ?? true);
            throw e;
        }
    };

    const cancelOrder = async (id: number): Promise<Order> => {
        errorMessage.value = null;

        try {
            const order = await cancelOrderRequest(id);
            if (current.value?.id === id) {
                current.value = order;
            }
            return order;
        } catch (e) {
            handleApiError(e);
            throw e;
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
        initiatePayment,
        syncPayment,
        cancelOrder,
    };
}

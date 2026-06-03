import type { Address } from '~/composables/useAddresses';
import type { Order } from '~/composables/useOrders';

export type CourierProfileStatus = 'ACTIVE' | 'SUSPENDED';
export type CourierVehicle = 'FOOT' | 'BIKE' | 'SCOOTER' | 'CAR';

export interface CourierProfile {
    user_id: number;
    status: CourierProfileStatus;
    vehicle: CourierVehicle | null;
    rating: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface CourierShift {
    id: number;
    courier_user_id: number;
    started_at: string;
    ended_at: string | null;
    status: string;
    created_at?: string;
    updated_at?: string;
}

export type CourierAddress = Omit<Partial<Address>, 'lat' | 'lng'> & {
    line2?: string | null;
    city?: string | null;
    postal_code?: string | null;
    lat?: number | string | null;
    lng?: number | string | null;
};

export interface CourierOrder extends Omit<Order, 'delivery_address'> {
    delivery_address?: CourierAddress | null;
    courier_estimated_fee?: string | null;
}

export interface CourierLocationPayload {
    lat: number;
    lng: number;
    accuracy?: number | null;
    heading?: number | null;
    speed?: number | null;
    recorded_at?: string | null;
}

export interface CourierEarningsBucket {
    deliveries_count: number;
    earnings_sum: string;
    total_orders_sum: string;
}

export interface CourierEarnings {
    today: CourierEarningsBucket;
    week: CourierEarningsBucket;
    total: CourierEarningsBucket;
}

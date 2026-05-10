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

export interface CourierAddress {
    id?: number;
    value?: string | null;
    unrestricted_value?: string | null;
    line1?: string | null;
    line2: string | null;
    city: string | null;
    postal_code: string | null;
    flat?: string | null;
    entrance?: string | null;
    floor?: string | null;
    lat?: number | string | null;
    lng?: number | string | null;
}

export interface CourierOrder extends Order {
    delivery_address?: CourierAddress | null;
}

export interface CourierLocationPayload {
    lat: number;
    lng: number;
    accuracy?: number | null;
    heading?: number | null;
    speed?: number | null;
    recorded_at?: string | null;
}

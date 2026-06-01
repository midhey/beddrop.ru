export type CourierProfileStatus = "ACTIVE" | "SUSPENDED";
export type CourierVehicle = "FOOT" | "BIKE" | "SCOOTER" | "CAR";

export interface CourierProfile {
  user_id: number;
  status: CourierProfileStatus;
  vehicle: CourierVehicle | null;
  rating: number | null;
}

export interface CourierShift {
  id: number;
  courier_user_id: number;
  started_at: string;
  ended_at: string | null;
  status: string;
}

export interface CourierAddress {
  value?: string | null;
  unrestricted_value?: string | null;
  line1?: string | null;
  city?: string | null;
  flat?: string | null;
  entrance?: string | null;
  floor?: string | null;
  lat?: number | string | null;
  lng?: number | string | null;
}

export interface RouteSegment {
  id: number;
  segment_type: string;
  encoded_shape: string | null;
  distance_meters: number | null;
  duration_seconds: number | null;
}

export interface CourierOrder {
  id: number;
  status: string;
  total_price: string | number | null;
  courier_fee?: string | number | null;
  courier_estimated_fee?: string | null;
  delivery_price_snapshot?: string | number | null;
  delivery_distance_meters?: number | null;
  delivery_duration_seconds?: number | null;
  estimated_delivery_at?: string | null;
  items_count?: number;
  created_at: string;
  updated_at?: string | null;
  restaurant?: { name?: string; address?: CourierAddress | null } | null;
  delivery_address?: CourierAddress | null;
  route_segments?: RouteSegment[];
  courier_approach_distance_meters?: number | null;
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

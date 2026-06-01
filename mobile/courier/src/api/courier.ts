import { api } from "@/api/client";
import type {
  CourierEarnings,
  CourierLocationPayload,
  CourierOrder,
  CourierProfile,
  CourierShift,
} from "@/domain/courier/types";

const unwrap = <T,>(raw: any): T => (raw?.data ?? raw) as T;

export const courierApi = {
  async profile(): Promise<CourierProfile> {
    const { data } = await api.get("/courier/profile");
    return data.profile ?? data;
  },
  async currentShift(): Promise<CourierShift | null> {
    const { data } = await api.get("/courier/shifts/current");
    return data.shift ?? null;
  },
  async startShift(): Promise<CourierShift | null> {
    const { data } = await api.post("/courier/shifts/start");
    return data.shift ?? null;
  },
  async endShift(): Promise<CourierShift | null> {
    const { data } = await api.post("/courier/shifts/end");
    return data.shift ?? null;
  },
  async available(): Promise<CourierOrder[]> {
    const { data } = await api.get("/courier/orders/available");
    return unwrap<CourierOrder[]>(data);
  },
  async active(): Promise<CourierOrder[]> {
    const { data } = await api.get("/courier/orders/active");
    return unwrap<CourierOrder[]>(data);
  },
  async history(): Promise<CourierOrder[]> {
    const { data } = await api.get("/courier/orders/history");
    return unwrap<CourierOrder[]>(data);
  },
  async earnings(): Promise<CourierEarnings> {
    const { data } = await api.get("/courier/earnings");
    return data;
  },
  async assign(id: number): Promise<CourierOrder> {
    const { data } = await api.post(`/courier/orders/${id}/assign`);
    return unwrap<CourierOrder>(data);
  },
  async pickedUp(id: number): Promise<CourierOrder> {
    const { data } = await api.post(`/courier/orders/${id}/picked-up`);
    return unwrap<CourierOrder>(data);
  },
  async delivered(id: number): Promise<CourierOrder> {
    const { data } = await api.post(`/courier/orders/${id}/delivered`);
    return unwrap<CourierOrder>(data);
  },
  async location(payload: CourierLocationPayload): Promise<void> {
    await api.post("/courier/location", payload);
  },
};

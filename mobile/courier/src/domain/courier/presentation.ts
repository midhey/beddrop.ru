import type { CourierAddress, CourierOrder, CourierShift, CourierVehicle } from "@/domain/courier/types";

const STATUS_LABELS: Record<string, string> = {
  CREATED: "Создан",
  ACCEPTED_BY_RESTAURANT: "Принят рестораном",
  READY_FOR_PICKUP: "Готов к выдаче",
  COURIER_ASSIGNED: "Курьер назначен",
  PICKED_UP: "В пути к клиенту",
  DELIVERED: "Доставлен",
  CANCELED_BY_USER: "Отменен клиентом",
  CANCELED_BY_RESTAURANT: "Отменен рестораном",
};

const VEHICLE_LABELS: Record<CourierVehicle, string> = {
  FOOT: "Пешком",
  BIKE: "Велосипед",
  SCOOTER: "Самокат",
  CAR: "Авто",
};

export const orderStatusLabel = (status: string) => STATUS_LABELS[status] ?? status;
export const vehicleLabel = (vehicle?: string | null) =>
  vehicle ? VEHICLE_LABELS[vehicle as CourierVehicle] ?? vehicle : "Не указан";
export const isShiftOpen = (shift?: CourierShift | null) =>
  !!shift && !shift.ended_at && shift.status === "OPEN";
export const canPickup = (order: Pick<CourierOrder, "status">) => order.status === "COURIER_ASSIGNED";
export const canDeliver = (order: Pick<CourierOrder, "status">) => order.status === "PICKED_UP";

export const formatPrice = (value: string | number | null | undefined) => {
  if (value == null) return "0 ₽";
  return `${Number(value).toLocaleString("ru-RU")} ₽`;
};

export const formatAddress = (address?: CourierAddress | null) => {
  const main = address?.value || address?.unrestricted_value || address?.line1;
  if (!main) return "";
  const parts: string[] = [];
  if (address?.city && !main.includes(address.city)) parts.push(address.city);
  parts.push(main);
  if (address?.entrance) parts.push(`подъезд ${address.entrance}`);
  if (address?.floor) parts.push(`этаж ${address.floor}`);
  if (address?.flat) parts.push(`кв. ${address.flat}`);
  return parts.join(", ");
};

export const courierPayout = (order: CourierOrder) => {
  const finalFee = Number(order.courier_fee ?? 0);
  if (Number.isFinite(finalFee) && finalFee > 0) return formatPrice(finalFee);
  const estimatedFee = Number(order.courier_estimated_fee ?? 0);
  if (Number.isFinite(estimatedFee) && estimatedFee > 0) return formatPrice(estimatedFee);
  return formatPrice(order.delivery_price_snapshot ?? order.total_price);
};

export const formatDistance = (meters?: number | null) => {
  if (!meters || meters <= 0) return "дистанция неизвестна";
  if (meters < 1000) return `${Math.round(meters)} м`;
  return `${(meters / 1000).toLocaleString("ru-RU", { maximumFractionDigits: 1 })} км`;
};

export const formatDuration = (seconds?: number | null) => {
  if (!seconds || seconds <= 0) return "время неизвестно";
  const minutes = Math.max(1, Math.round(seconds / 60));
  return `${minutes} мин`;
};

export const formatDeliveryArea = (address?: CourierAddress | null) => {
  if (!address) return "район доставки скрыт до принятия";
  return address.city || address.line1 || address.value || "район доставки скрыт до принятия";
};

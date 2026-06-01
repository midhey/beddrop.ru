import MapView, { Marker, Polyline, type LatLng } from "react-native-maps";
import polyline from "@mapbox/polyline";
import type { CourierOrder } from "@/domain/courier/types";
import { colors, radii } from "@/theme/tokens";

const decodeShape = (shape?: string | null): LatLng[] => {
  if (!shape) return [];
  return polyline.decode(shape, 6).map(([latitude, longitude]) => ({ latitude, longitude }));
};

export function RouteMap({ order, courier }: { order: CourierOrder; courier?: { lat: number; lng: number } | null }) {
  const segments = order.route_segments ?? [];
  const points = segments.flatMap((segment) => decodeShape(segment.encoded_shape));
  const rest = order.restaurant?.address;
  const dest = order.delivery_address;
  const initial = points[0] ??
    (rest?.lat && rest?.lng
      ? { latitude: Number(rest.lat), longitude: Number(rest.lng) }
      : { latitude: 58.521, longitude: 31.275 });

  return (
    <MapView style={{ height: 300, borderRadius: radii.lg }} initialRegion={{ ...initial, latitudeDelta: 0.05, longitudeDelta: 0.05 }}>
      {segments.map((segment) => (
        <Polyline key={segment.id} coordinates={decodeShape(segment.encoded_shape)} strokeWidth={4} strokeColor={colors.primary} />
      ))}
      {rest?.lat != null && rest?.lng != null ? (
        <Marker coordinate={{ latitude: Number(rest.lat), longitude: Number(rest.lng) }} title="Ресторан" pinColor="orange" />
      ) : null}
      {dest?.lat != null && dest?.lng != null ? (
        <Marker coordinate={{ latitude: Number(dest.lat), longitude: Number(dest.lng) }} title="Доставка" pinColor="green" />
      ) : null}
      {courier ? <Marker coordinate={{ latitude: courier.lat, longitude: courier.lng }} title="Вы" pinColor="blue" /> : null}
    </MapView>
  );
}

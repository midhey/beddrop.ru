import { useEffect, useMemo, useRef } from "react";
import { Text, View } from "react-native";
import MapView, { Marker, Polyline, type LatLng, type Region } from "react-native-maps";
import type * as Location from "expo-location";
import polyline from "@mapbox/polyline";
import { Bike, MapPin, Navigation, PackageCheck } from "lucide-react-native";
import type { WorkdayMode } from "@/hooks/use-courier-workday";
import type { CourierAddress, CourierOrder, RouteSegment } from "@/domain/courier/types";
import { colors, iconSizes, iconStrokeWidth, radii } from "@/theme/tokens";

const DEFAULT_REGION: Region = {
  latitude: 58.521,
  longitude: 31.275,
  latitudeDelta: 0.045,
  longitudeDelta: 0.045,
};

const decodeShape = (shape?: string | null): LatLng[] => {
  if (!shape) return [];
  try {
    return polyline.decode(shape, 6).map(([latitude, longitude]) => ({ latitude, longitude }));
  } catch {
    return [];
  }
};

const coordinateFromAddress = (address?: CourierAddress | null): LatLng | null => {
  if (address?.lat == null || address.lng == null) return null;
  const latitude = Number(address.lat);
  const longitude = Number(address.lng);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
  return { latitude, longitude };
};

const statusText = (active: boolean, status: string) => {
  if (!active) return "Не на смене";
  if (status === "tracking") return "На линии";
  if (status === "denied") return "Нет доступа к геолокации";
  if (status === "error") return "Геолокация недоступна";
  return "Ищем позицию";
};

export function ShiftLiveMap({
  active,
  location,
  status,
  height,
  order,
  mode,
  routeSegments,
}: {
  active: boolean;
  location: Location.LocationObject | null;
  status: string;
  height: number;
  order?: CourierOrder | null;
  mode: WorkdayMode;
  routeSegments: RouteSegment[];
}) {
  const mapRef = useRef<MapView | null>(null);
  const coordinate = useMemo(() => {
    if (!location) return null;

    return {
      latitude: location.coords.latitude,
      longitude: location.coords.longitude,
    };
  }, [location]);
  const restaurantCoordinate = useMemo(() => coordinateFromAddress(order?.restaurant?.address), [order?.restaurant?.address]);
  const deliveryCoordinate = useMemo(
    () => (mode === "toClient" ? coordinateFromAddress(order?.delivery_address) : null),
    [mode, order?.delivery_address],
  );
  const routeLines = useMemo(
    () =>
      routeSegments
        .map((segment) => ({
          id: segment.id,
          type: segment.segment_type,
          coordinates: decodeShape(segment.encoded_shape),
        }))
        .filter((segment) => segment.coordinates.length > 1),
    [routeSegments],
  );
  const routePoints = useMemo(() => routeLines.flatMap((segment) => segment.coordinates), [routeLines]);

  const region = coordinate
    ? { ...coordinate, latitudeDelta: 0.018, longitudeDelta: 0.018 }
    : DEFAULT_REGION;

  useEffect(() => {
    const points = [
      ...routePoints,
      ...(coordinate ? [coordinate] : []),
      ...(restaurantCoordinate ? [restaurantCoordinate] : []),
      ...(deliveryCoordinate ? [deliveryCoordinate] : []),
    ];

    if (points.length > 1) {
      mapRef.current?.fitToCoordinates(points, {
        edgePadding: { top: 90, right: 46, bottom: 260, left: 46 },
        animated: true,
      });
      return;
    }

    const focus = restaurantCoordinate ?? coordinate;
    if (!focus) return;

    mapRef.current?.animateToRegion(
      { ...focus, latitudeDelta: 0.018, longitudeDelta: 0.018 },
      500,
    );
  }, [coordinate, deliveryCoordinate, restaurantCoordinate, routePoints]);

  return (
    <View style={{ height, overflow: "hidden", backgroundColor: colors.mapDark }}>
      <MapView
        ref={mapRef}
        style={{ height }}
        initialRegion={region}
        showsCompass={false}
        showsMyLocationButton={false}
        toolbarEnabled={false}
      >
        {routeLines.map((segment) => (
          <Polyline
            key={segment.id}
            coordinates={segment.coordinates}
            strokeWidth={5}
            strokeColor={segment.type === "courier_to_restaurant" ? colors.secondary : colors.primary}
            lineCap="round"
            lineJoin="round"
          />
        ))}
        {restaurantCoordinate ? (
          <Marker coordinate={restaurantCoordinate} title={order?.restaurant?.name ?? "Ресторан"}>
            <View
              style={{
                width: 34,
                height: 34,
                alignItems: "center",
                justifyContent: "center",
                borderRadius: 17,
                borderWidth: 3,
                borderColor: colors.surface,
                backgroundColor: colors.primary,
              }}
            >
              <MapPin color={colors.surface} size={iconSizes.md} strokeWidth={iconStrokeWidth} />
            </View>
          </Marker>
        ) : null}
        {deliveryCoordinate ? (
          <Marker coordinate={deliveryCoordinate} title="Клиент">
            <View
              style={{
                width: 34,
                height: 34,
                alignItems: "center",
                justifyContent: "center",
                borderRadius: 17,
                borderWidth: 3,
                borderColor: colors.surface,
                backgroundColor: colors.successText,
              }}
            >
              <PackageCheck color={colors.surface} size={iconSizes.md} strokeWidth={iconStrokeWidth} />
            </View>
          </Marker>
        ) : null}
        {coordinate ? (
          <Marker coordinate={coordinate} title="Вы">
            <View
              style={{
                width: 24,
                height: 24,
                borderRadius: 12,
                borderWidth: 4,
                borderColor: colors.surface,
                backgroundColor: colors.secondary,
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <Bike color={colors.textStrong} size={12} strokeWidth={2.2} />
            </View>
          </Marker>
        ) : null}
      </MapView>

      <View style={{ position: "absolute", top: 14, left: 14, right: 14, gap: 8 }}>
        <View
          style={{
            flexDirection: "row",
            alignItems: "center",
            gap: 8,
            alignSelf: "flex-start",
            paddingVertical: 8,
            paddingHorizontal: 12,
            borderRadius: radii.pill,
            backgroundColor: active ? colors.mapDark : colors.surface,
          }}
        >
          <Navigation color={active ? colors.secondary : colors.textStrong} size={iconSizes.sm} strokeWidth={iconStrokeWidth} />
          <Text selectable style={{ color: active ? colors.secondary : colors.textStrong, fontWeight: "900" }}>
            {statusText(active, status)}
          </Text>
        </View>
      </View>
    </View>
  );
}

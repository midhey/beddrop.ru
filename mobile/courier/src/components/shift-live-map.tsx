import { useEffect, useMemo, useRef } from "react";
import { Text, View } from "react-native";
import { Camera, type CameraRef, GeoJSONSource, Layer, Map, Marker, type LngLat, type LngLatBounds } from "@maplibre/maplibre-react-native";
import type { StyleSpecification } from "@maplibre/maplibre-gl-style-spec";
import type * as Location from "expo-location";
import { Bike, MapPin, Navigation, PackageCheck } from "lucide-react-native";
import type { WorkdayMode } from "@/hooks/use-courier-workday";
import type { CourierAddress, CourierOrder, RouteSegment } from "@/domain/courier/types";
import { colors, iconSizes, iconStrokeWidth, radii } from "@/theme/tokens";

const DEFAULT_CENTER: LngLat = [31.275, 58.521];
const MAP_STYLE: StyleSpecification = {
  version: 8,
  sources: {
    osm: {
      type: "raster",
      tiles: ["https://tile.openstreetmap.org/{z}/{x}/{y}.png"],
      tileSize: 256,
      attribution: "© OpenStreetMap contributors",
    },
  },
  layers: [
    {
      id: "osm",
      type: "raster",
      source: "osm",
    },
  ],
};

const decodeShape = (shape?: string | null): LngLat[] => {
  if (!shape) return [];

  try {
    let index = 0;
    let lat = 0;
    let lng = 0;
    const coordinates: LngLat[] = [];
    const factor = 1e6;

    while (index < shape.length) {
      let byte;
      let shift = 0;
      let result = 0;

      do {
        byte = shape.charCodeAt(index++) - 63;
        result |= (byte & 0x1f) << shift;
        shift += 5;
      } while (byte >= 0x20);

      lat += (result & 1) ? ~(result >> 1) : (result >> 1);
      shift = 0;
      result = 0;

      do {
        byte = shape.charCodeAt(index++) - 63;
        result |= (byte & 0x1f) << shift;
        shift += 5;
      } while (byte >= 0x20);

      lng += (result & 1) ? ~(result >> 1) : (result >> 1);
      coordinates.push([lng / factor, lat / factor]);
    }

    return coordinates;
  } catch {
    return [];
  }
};

const coordinateFromAddress = (address?: CourierAddress | null): LngLat | null => {
  if (address?.lat == null || address.lng == null) return null;
  const latitude = Number(address.lat);
  const longitude = Number(address.lng);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
  return [longitude, latitude];
};

const boundsFromPoints = (points: LngLat[]): LngLatBounds | null => {
  if (!points.length) return null;

  const [firstLng, firstLat] = points[0];
  let west = firstLng;
  let east = firstLng;
  let south = firstLat;
  let north = firstLat;

  for (const [lng, lat] of points) {
    west = Math.min(west, lng);
    east = Math.max(east, lng);
    south = Math.min(south, lat);
    north = Math.max(north, lat);
  }

  return [west, south, east, north];
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
  const cameraRef = useRef<CameraRef | null>(null);
  const coordinate = useMemo(() => {
    if (!location) return null;

    return [location.coords.longitude, location.coords.latitude] satisfies LngLat;
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
  const initialCenter = coordinate ?? restaurantCoordinate ?? DEFAULT_CENTER;

  useEffect(() => {
    const points = [
      ...routePoints,
      ...(coordinate ? [coordinate] : []),
      ...(restaurantCoordinate ? [restaurantCoordinate] : []),
      ...(deliveryCoordinate ? [deliveryCoordinate] : []),
    ];

    if (points.length > 1) {
      const bounds = boundsFromPoints(points);
      if (bounds) {
        cameraRef.current?.fitBounds(bounds, { padding: { top: 90, right: 46, bottom: 260, left: 46 }, duration: 500 });
      }
      return;
    }

    const focus = restaurantCoordinate ?? coordinate;
    if (!focus) return;

    cameraRef.current?.easeTo({ center: focus, zoom: 14, duration: 500 });
  }, [coordinate, deliveryCoordinate, restaurantCoordinate, routePoints]);

  return (
    <View style={{ height, overflow: "hidden", backgroundColor: colors.mapDark }}>
      <Map
        style={{ height }}
        mapStyle={MAP_STYLE}
        compass={false}
        logo={false}
        scaleBar={false}
        attributionPosition={{ bottom: 8, left: 8 }}
      >
        <Camera ref={cameraRef} initialViewState={{ center: initialCenter, zoom: 13 }} />
        {routeLines.map((segment) => (
          <GeoJSONSource
            key={segment.id}
            id={`route-${segment.id}`}
            data={{
              type: "Feature",
              geometry: {
                type: "LineString",
                coordinates: segment.coordinates,
              },
              properties: {},
            }}
          >
            <Layer
              id={`route-layer-${segment.id}`}
              type="line"
              paint={{
                "line-color": segment.type === "courier_to_restaurant" ? colors.secondary : colors.primary,
                "line-width": 5,
                "line-opacity": 0.9,
              }}
              layout={{
                "line-cap": "round",
                "line-join": "round",
              }}
            />
          </GeoJSONSource>
        ))}
        {restaurantCoordinate ? (
          <Marker lngLat={restaurantCoordinate} id="restaurant">
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
          <Marker lngLat={deliveryCoordinate} id="delivery">
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
          <Marker lngLat={coordinate} id="courier">
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
      </Map>

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

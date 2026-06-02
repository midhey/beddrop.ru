import { View } from "react-native";
import { Camera, GeoJSONSource, Layer, Map, Marker, type LngLat } from "@maplibre/maplibre-react-native";
import type { StyleSpecification } from "@maplibre/maplibre-gl-style-spec";
import type { CourierOrder } from "@/domain/courier/types";
import { colors, radii } from "@/theme/tokens";

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

const coordinate = (lat?: number | string | null, lng?: number | string | null): LngLat | null => {
  if (lat == null || lng == null) return null;
  const latitude = Number(lat);
  const longitude = Number(lng);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
  return [longitude, latitude];
};

export function RouteMap({ order, courier }: { order: CourierOrder; courier?: { lat: number; lng: number } | null }) {
  const segments = (order.route_segments ?? [])
    .map((segment) => ({
      id: segment.id,
      coordinates: decodeShape(segment.encoded_shape),
    }))
    .filter((segment) => segment.coordinates.length > 1);
  const rest = order.restaurant?.address;
  const dest = order.delivery_address;
  const restaurantCoordinate = coordinate(rest?.lat, rest?.lng);
  const deliveryCoordinate = coordinate(dest?.lat, dest?.lng);
  const courierCoordinate = courier ? coordinate(courier.lat, courier.lng) : null;
  const initial = segments[0]?.coordinates[0] ?? restaurantCoordinate ?? DEFAULT_CENTER;

  return (
    <Map
      style={{ height: 300, borderRadius: radii.lg }}
      mapStyle={MAP_STYLE}
      compass={false}
      logo={false}
      scaleBar={false}
      attributionPosition={{ bottom: 8, left: 8 }}
    >
      <Camera initialViewState={{ center: initial, zoom: 13 }} />
      {segments.map((segment) => (
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
              "line-color": colors.primary,
              "line-width": 4,
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
          <View style={{ width: 16, height: 16, borderRadius: 8, backgroundColor: colors.secondary }} />
        </Marker>
      ) : null}
      {deliveryCoordinate ? (
        <Marker lngLat={deliveryCoordinate} id="delivery">
          <View style={{ width: 16, height: 16, borderRadius: 8, backgroundColor: colors.successText }} />
        </Marker>
      ) : null}
      {courierCoordinate ? (
        <Marker lngLat={courierCoordinate} id="courier">
          <View style={{ width: 14, height: 14, borderRadius: 7, backgroundColor: colors.primary }} />
        </Marker>
      ) : null}
    </Map>
  );
}

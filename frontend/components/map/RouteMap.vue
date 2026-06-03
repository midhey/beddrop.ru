<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Address } from '~/composables/useAddresses';
import type { OrderRouteSegment } from '~/composables/useOrders';

type MappablePoint = Omit<Partial<Address>, 'lat' | 'lng'> & {
  lat?: number | string | null;
  lng?: number | string | null;
};

declare global {
  interface Window {
    maplibregl?: any;
  }
}

const props = withDefaults(
  defineProps<{
    routeSegments?: OrderRouteSegment[] | null;
    restaurantAddress?: MappablePoint | null;
    deliveryAddress?: MappablePoint | null;
    courierLocation?: MappablePoint | null;
    height?: number;
  }>(),
  {
    routeSegments: null,
    restaurantAddress: null,
    deliveryAddress: null,
    courierLocation: null,
    height: 300,
  },
);

const config = useRuntimeConfig();
const mapEl = ref<HTMLElement | null>(null);
const mapLoadError = ref(false);
let map: any = null;
let markers: any[] = [];

const drawableSegments = computed(() => {
  return (props.routeSegments ?? [])
    .filter((segment) => !!segment.encoded_shape)
    .map((segment) => ({
      ...segment,
      coordinates: decodeValhallaPolyline(segment.encoded_shape || ''),
    }))
    .filter((segment) => segment.coordinates.length > 1);
});

const hasRoutes = computed(() => drawableSegments.value.length > 0);
const markerCoordinates = computed<[number, number][]>(() => {
  const points = [props.restaurantAddress, props.deliveryAddress, props.courierLocation];

  return points
    .filter((point) => point?.lat != null && point?.lng != null)
    .map((point) => [Number(point?.lng), Number(point?.lat)] as [number, number]);
});
const hasMarkers = computed(() => markerCoordinates.value.length > 0);
const segmentColor = (segmentType: string) => {
  if (segmentType === 'courier_to_restaurant') return '#f97316';
  return '#2563eb';
};

const decodeValhallaPolyline = (shape: string): [number, number][] => {
  let index = 0;
  let lat = 0;
  let lng = 0;
  const coordinates: [number, number][] = [];
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
};

const loadMapLibre = async () => {
  if (typeof window === 'undefined') return false;
  if (window.maplibregl) return true;

  if (!document.querySelector('link[data-maplibre]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/maplibre-gl@4.1.2/dist/maplibre-gl.css';
    link.dataset.maplibre = 'true';
    document.head.appendChild(link);
  }

  await new Promise<void>((resolve, reject) => {
    const existing = document.querySelector('script[data-maplibre]') as HTMLScriptElement | null;
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('MapLibre failed')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://unpkg.com/maplibre-gl@4.1.2/dist/maplibre-gl.js';
    script.async = true;
    script.dataset.maplibre = 'true';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('MapLibre failed'));
    document.head.appendChild(script);
  });

  return !!window.maplibregl;
};

const createMap = async () => {
  if (!mapEl.value || map || (!hasRoutes.value && !hasMarkers.value)) return;

  try {
    const loaded = await loadMapLibre();
    if (!loaded || !window.maplibregl) return;

    const firstCoord = drawableSegments.value[0]?.coordinates[0] ?? markerCoordinates.value[0];
    map = new window.maplibregl.Map({
      container: mapEl.value,
      style: {
        version: 8,
        sources: {
          osm: {
            type: 'raster',
            tiles: [config.public.mapTileUrl],
            tileSize: 256,
            attribution: config.public.mapTileAttribution,
          },
        },
        layers: [
          {
            id: 'osm',
            type: 'raster',
            source: 'osm',
          },
        ],
      },
      center: firstCoord,
      zoom: 13,
    });

    map.on('load', drawRoutes);
  } catch {
    mapLoadError.value = true;
  }
};

const clearMarkers = () => {
  markers.forEach((marker) => marker.remove());
  markers = [];
};

const drawMarker = (address: MappablePoint | null | undefined, color: string) => {
  if (!map || !window.maplibregl || address?.lat == null || address?.lng == null) return;

  markers.push(
    new window.maplibregl.Marker({ color })
      .setLngLat([Number(address.lng), Number(address.lat)])
      .addTo(map),
  );
};

const drawRoutes = () => {
  if (!map || (!hasRoutes.value && !hasMarkers.value)) return;

  clearMarkers();

  for (const segment of drawableSegments.value) {
    const sourceId = `route-${segment.id}`;
    const layerId = `route-layer-${segment.id}`;

    if (map.getLayer(layerId)) map.removeLayer(layerId);
    if (map.getSource(sourceId)) map.removeSource(sourceId);

    map.addSource(sourceId, {
      type: 'geojson',
      data: {
        type: 'Feature',
        geometry: {
          type: 'LineString',
          coordinates: segment.coordinates,
        },
      },
    });

    map.addLayer({
      id: layerId,
      type: 'line',
      source: sourceId,
      paint: {
        'line-color': segmentColor(segment.segment_type),
        'line-width': segment.segment_type === 'courier_to_restaurant' ? 5 : 6,
        'line-opacity': 0.9,
      },
    });
  }

  drawMarker(props.restaurantAddress, '#10b981');
  drawMarker(props.deliveryAddress, '#ef4444');
  drawMarker(props.courierLocation, '#f97316');

  const bounds = new window.maplibregl.LngLatBounds();
  drawableSegments.value.forEach((segment) => {
    segment.coordinates.forEach((coord) => bounds.extend(coord));
  });
  markerCoordinates.value.forEach((coord) => bounds.extend(coord));

  if (!bounds.isEmpty()) {
    map.fitBounds(bounds, { padding: 42, maxZoom: 15 });
  }
};

onMounted(async () => {
  await nextTick();
  await createMap();
});

watch(drawableSegments, async () => {
  await nextTick();
  if (!map) {
    await createMap();
    return;
  }
  drawRoutes();
});

watch(() => [props.restaurantAddress, props.deliveryAddress, props.courierLocation], async () => {
  await nextTick();
  if (!map) {
    await createMap();
    return;
  }
  drawRoutes();
}, { deep: true });

onBeforeUnmount(() => {
  clearMarkers();
  if (map) {
    map.remove();
    map = null;
  }
});
</script>

<template>
  <div v-if="hasRoutes || hasMarkers" class="route-map">
    <div
      ref="mapEl"
      class="route-map__canvas"
      :style="{ minHeight: `calc(${height} / 20 * 1rem)` }"
    />
    <p v-if="mapLoadError" class="route-map__fallback state-message state-message--empty">
      Карта маршрута недоступна.
    </p>
  </div>
</template>

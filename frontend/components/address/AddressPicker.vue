<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { LocateFixed, MapPin, Search } from 'lucide-vue-next';
import { useGeo, type AddressSuggestion } from '~/composables/useGeo';
import type { AddressPayload } from '~/composables/useAddresses';

declare global {
  interface Window {
    maplibregl?: any;
  }
}

const props = withDefaults(
  defineProps<{
    required?: boolean;
    placeholder?: string;
    mapHeight?: number;
  }>(),
  {
    required: false,
    placeholder: 'Город, улица, дом',
    mapHeight: 300,
  },
);

const model = defineModel<AddressPayload>({
  required: true,
});

const config = useRuntimeConfig();
const { fetchAddressSuggestions, reverseGeocode, loading } = useGeo();
const query = ref(model.value.value || model.value.unrestricted_value || model.value.line1 || '');
const suggestions = ref<AddressSuggestion[]>([]);
const suggestionsOpen = ref(false);
const mapEl = ref<HTMLElement | null>(null);
const mapReady = ref(false);
const mapLoadError = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let map: any = null;
let marker: any = null;

const hasCoordinates = computed(() => model.value.lat != null && model.value.lng != null);
const selectedAddressText = computed(() => model.value.value || model.value.unrestricted_value || model.value.line1 || '');

const setModel = (patch: AddressPayload) => {
  model.value = {
    ...model.value,
    ...patch,
    line1: patch.line1 ?? patch.value ?? patch.unrestricted_value ?? model.value.line1 ?? null,
  };
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

const ensureMap = async () => {
  if (!mapEl.value || !hasCoordinates.value || map) return;

  try {
    const loaded = await loadMapLibre();
    if (!loaded || !window.maplibregl) return;

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
      center: [Number(model.value.lng), Number(model.value.lat)],
      zoom: 15,
    });

    marker = new window.maplibregl.Marker({ draggable: true })
      .setLngLat([Number(model.value.lng), Number(model.value.lat)])
      .addTo(map);

    marker.on('dragend', async () => {
      const point = marker.getLngLat();
      await applyReverse(point.lat, point.lng);
    });

    map.on('click', async (event: any) => {
      await applyReverse(event.lngLat.lat, event.lngLat.lng);
    });

    mapReady.value = true;
  } catch {
    mapLoadError.value = true;
  }
};

const syncMarker = () => {
  if (!map || !marker || !hasCoordinates.value) return;

  const lngLat = [Number(model.value.lng), Number(model.value.lat)];
  marker.setLngLat(lngLat);
  map.setCenter(lngLat);
};

const applySuggestion = async (suggestion: AddressSuggestion) => {
  const rawData = suggestion.raw?.data ?? suggestion.data.raw_dadata_json ?? null;
  setModel({
    ...suggestion.data,
    raw_dadata_json: rawData,
    value: suggestion.value ?? suggestion.data.value ?? null,
    unrestricted_value: suggestion.unrestricted_value ?? suggestion.data.unrestricted_value ?? null,
    geo_source: 'dadata',
  });
  query.value = selectedAddressText.value;
  suggestions.value = [];
  suggestionsOpen.value = false;
  await nextTick();
  await ensureMap();
  syncMarker();
};

const applyReverse = async (lat: number, lng: number) => {
  setModel({ lat, lng });
  syncMarker();

  try {
    const suggestion = await reverseGeocode(lat, lng);
    if (suggestion) {
      await applySuggestion(suggestion);
    }
  } catch {
  }
};

const search = async () => {
  const value = query.value.trim();
  if (value.length < 2) {
    suggestions.value = [];
    return;
  }

  try {
    suggestions.value = await fetchAddressSuggestions(value);
    suggestionsOpen.value = true;
  } catch {
    suggestions.value = [];
  }
};

const scheduleSearch = () => {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(search, 350);
};

watch(
  () => [model.value.lat, model.value.lng],
  async () => {
    if (hasCoordinates.value) {
      await nextTick();
      await ensureMap();
      syncMarker();
    }
  },
);

watch(
  () => model.value.value,
  (value) => {
    if (value && value !== query.value) {
      query.value = value;
    }
  },
);

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
  if (map) {
    map.remove();
    map = null;
    marker = null;
  }
});
</script>

<template>
  <div class="address-picker">
    <div class="address-picker__search form-field">
      <label class="form-field__label">
        Адрес
        <span v-if="required" class="address-picker__required">*</span>
      </label>
      <div class="address-picker__search-control">
        <Search class="address-picker__search-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
        <input
          v-model="query"
          type="text"
          class="address-picker__input field-input"
          :placeholder="placeholder"
          :required="required"
          autocomplete="off"
          @input="scheduleSearch"
          @focus="suggestionsOpen = suggestions.length > 0"
        >
      </div>

      <div v-if="suggestionsOpen && suggestions.length" class="address-picker__suggestions">
        <button
          v-for="suggestion in suggestions"
          :key="suggestion.unrestricted_value || suggestion.value || JSON.stringify(suggestion.raw)"
          type="button"
          class="address-picker__suggestion"
          @click="applySuggestion(suggestion)"
        >
          <MapPin class="address-picker__suggestion-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
          <span>{{ suggestion.value }}</span>
        </button>
      </div>
    </div>

    <div v-if="selectedAddressText" class="address-picker__selected">
      <LocateFixed :size="18" :stroke-width="1.9" aria-hidden="true" />
      <span>{{ selectedAddressText }}</span>
    </div>

    <div
      v-show="hasCoordinates"
      ref="mapEl"
      class="address-picker__map"
      :style="{ minHeight: `${mapHeight}px` }"
    />

    <div v-if="hasCoordinates && mapLoadError" class="address-picker__map-fallback state-message state-message--empty">
      Карта недоступна, но координаты адреса сохранены.
    </div>

    <div class="address-picker__details">
      <div class="form-field">
        <label class="form-field__label">Название</label>
        <input v-model="model.label" type="text" class="field-input" placeholder="Дом, работа..." >
      </div>

      <div class="address-picker__details-grid">
        <div class="form-field">
          <label class="form-field__label">Квартира</label>
          <input v-model="model.flat" type="text" class="field-input" placeholder="42" >
        </div>
        <div class="form-field">
          <label class="form-field__label">Подъезд</label>
          <input v-model="model.entrance" type="text" class="field-input" placeholder="3" >
        </div>
        <div class="form-field">
          <label class="form-field__label">Этаж</label>
          <input v-model="model.floor" type="text" class="field-input" placeholder="8" >
        </div>
        <div class="form-field">
          <label class="form-field__label">Домофон</label>
          <input v-model="model.intercom" type="text" class="field-input" placeholder="1234" >
        </div>
      </div>
    </div>

    <div v-if="loading" class="address-picker__loading">
      Ищем адрес...
    </div>
  </div>
</template>

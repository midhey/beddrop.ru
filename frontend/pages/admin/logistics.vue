<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { Settings2, MapPinned, Route, Search } from 'lucide-vue-next';
import RouteMap from '~/components/map/RouteMap.vue';
import { useAdminLogistics } from '~/composables/useAdminLogistics';
import { useFeedback } from '~/composables/useFeedback';

definePageMeta({
  middleware: 'access',
});

useSeoMeta({
  title: 'Логистика — админка BedDrop',
});

const feedback = useFeedback();
const {
  groups,
  loading,
  saving,
  debugLoading,
  errorMessage,
  addressDebugResult,
  routeDebugResult,
  orderRoutesResult,
  fetchSettings,
  saveSettings,
  testAddress,
  testRoute,
  fetchOrderRoutes,
} = useAdminLogistics();

const addressQuery = ref('Великий Новгород, Большая Санкт-Петербургская, 1');
const orderId = ref('');
const routeForm = reactive({
  fromLat: '58.525',
  fromLng: '31.275',
  toLat: '58.53',
  toLng: '31.28',
  mode: 'auto',
});

const groupLabels: Record<string, string> = {
  pricing: 'Стоимость',
  time: 'Время',
  valhalla_auto: 'Valhalla auto',
  valhalla_bicycle: 'Valhalla bicycle',
  valhalla_pedestrian: 'Valhalla pedestrian',
};

const inputType = (type: string) => {
  if (type === 'integer' || type === 'decimal') return 'number';
  return 'text';
};

const inputStep = (type: string) => {
  if (type === 'integer') return '1';
  if (type === 'decimal') return '0.1';
  return undefined;
};

const doSaveSettings = async () => {
  await saveSettings();
  feedback.success('Настройки логистики сохранены');
};

const doTestAddress = async () => {
  if (!addressQuery.value.trim()) return;
  await testAddress(addressQuery.value.trim());
};

const doTestRoute = async () => {
  await testRoute({
    mode: routeForm.mode,
    from: {
      lat: Number(routeForm.fromLat),
      lng: Number(routeForm.fromLng),
    },
    to: {
      lat: Number(routeForm.toLat),
      lng: Number(routeForm.toLng),
    },
  });
};

const doFetchOrderRoutes = async () => {
  const id = Number(orderId.value);
  if (!id) return;
  await fetchOrderRoutes(id);
};

onMounted(fetchSettings);
</script>

<template>
  <section class="admin-logistics page-shell">
    <div class="admin-logistics__container container">
      <div class="page-head">
        <div>
          <h1 class="page-title">Логистика</h1>
          <p class="page-subtitle">
            Настройки доставки, проверка DaData и Valhalla, просмотр маршрутов заказов.
          </p>
        </div>
      </div>

      <p v-if="errorMessage" class="state-message state-message--error">
        {{ errorMessage }}
      </p>

      <div class="admin-logistics__grid">
        <section class="admin-logistics__panel surface-card">
          <div class="section-head">
            <h2 class="section-title">
              <Settings2 class="ui-icon" :size="19" :stroke-width="1.9" aria-hidden="true" />
              Настройки доставки
            </h2>
          </div>

          <div v-if="loading" class="state-message state-message--loading">
            Загружаем настройки...
          </div>

          <div v-else class="admin-logistics__settings">
            <div
              v-for="(settings, group) in groups"
              :key="group"
              class="admin-logistics__group"
            >
              <h3 class="admin-logistics__group-title">
                {{ groupLabels[group] || group }}
              </h3>

              <div class="admin-logistics__fields">
                <label
                  v-for="setting in settings"
                  :key="setting.key"
                  class="admin-logistics__field form-field"
                >
                  <span class="form-field__label">{{ setting.label }}</span>
                  <select
                    v-if="setting.type === 'boolean'"
                    v-model="setting.value"
                    class="field-input"
                  >
                    <option value="1">Да</option>
                    <option value="0">Нет</option>
                  </select>
                  <input
                    v-else
                    v-model="setting.value"
                    :type="inputType(setting.type)"
                    :step="inputStep(setting.type)"
                    class="field-input"
                  >
                  <small v-if="setting.description" class="admin-logistics__hint">
                    {{ setting.description }}
                  </small>
                </label>
              </div>
            </div>

            <button
              type="button"
              class="button"
              :disabled="saving"
              @click="doSaveSettings"
            >
              {{ saving ? 'Сохраняем...' : 'Сохранить настройки' }}
            </button>
          </div>
        </section>

        <aside class="admin-logistics__debug">
          <section class="admin-logistics__panel surface-card">
            <div class="section-head">
              <h2 class="section-title">
                <Search class="ui-icon" :size="19" :stroke-width="1.9" aria-hidden="true" />
                DaData
              </h2>
            </div>

            <div class="admin-logistics__inline-form">
              <input v-model="addressQuery" type="text" class="field-input" placeholder="Адрес для проверки">
              <button type="button" class="button" :disabled="debugLoading" @click="doTestAddress">
                Проверить
              </button>
            </div>

            <pre v-if="addressDebugResult" class="admin-logistics__json">{{ JSON.stringify(addressDebugResult, null, 2) }}</pre>
          </section>

          <section class="admin-logistics__panel surface-card">
            <div class="section-head">
              <h2 class="section-title">
                <Route class="ui-icon" :size="19" :stroke-width="1.9" aria-hidden="true" />
                Valhalla
              </h2>
            </div>

            <div class="admin-logistics__route-form">
              <input v-model="routeForm.fromLat" type="number" step="0.000001" class="field-input" placeholder="От lat">
              <input v-model="routeForm.fromLng" type="number" step="0.000001" class="field-input" placeholder="От lng">
              <input v-model="routeForm.toLat" type="number" step="0.000001" class="field-input" placeholder="До lat">
              <input v-model="routeForm.toLng" type="number" step="0.000001" class="field-input" placeholder="До lng">
              <select v-model="routeForm.mode" class="field-input">
                <option value="auto">auto</option>
                <option value="bicycle">bicycle</option>
                <option value="pedestrian">pedestrian</option>
              </select>
              <button type="button" class="button" :disabled="debugLoading" @click="doTestRoute">
                Построить
              </button>
            </div>

            <div v-if="routeDebugResult" class="admin-logistics__route-result">
              <p>
                {{ (routeDebugResult.distance_meters / 1000).toFixed(2) }} км,
                {{ Math.ceil(routeDebugResult.duration_seconds / 60) }} мин
              </p>
              <RouteMap
                :route-segments="[{
                  id: 0,
                  order_id: 0,
                  segment_type: 'restaurant_to_client',
                  mode: routeDebugResult.mode,
                  distance_meters: routeDebugResult.distance_meters,
                  duration_seconds: routeDebugResult.duration_seconds,
                  encoded_shape: routeDebugResult.encoded_shape,
                }]"
                :height="260"
              />
            </div>
          </section>

          <section class="admin-logistics__panel surface-card">
            <div class="section-head">
              <h2 class="section-title">
                <MapPinned class="ui-icon" :size="19" :stroke-width="1.9" aria-hidden="true" />
                Маршруты заказа
              </h2>
            </div>

            <div class="admin-logistics__inline-form">
              <input v-model="orderId" type="number" min="1" step="1" class="field-input" placeholder="ID заказа">
              <button type="button" class="button" :disabled="debugLoading" @click="doFetchOrderRoutes">
                Открыть
              </button>
            </div>

            <RouteMap
              v-if="orderRoutesResult?.route_segments?.length"
              :route-segments="orderRoutesResult.route_segments"
              :height="260"
            />
            <pre v-if="orderRoutesResult" class="admin-logistics__json">{{ JSON.stringify(orderRoutesResult, null, 2) }}</pre>
          </section>
        </aside>
      </div>
    </div>
  </section>
</template>

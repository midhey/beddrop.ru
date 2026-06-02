<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { Settings2, MapPinned, Route, Search } from 'lucide-vue-next';
import AdminShell from '~/components/admin/AdminShell.vue';
import RouteMap from '~/components/map/RouteMap.vue';
import BaseSwitch from '~/components/ui/BaseSwitch.vue';
import { useAdminLogistics } from '~/composables/useAdminLogistics';
import { useFeedback } from '~/composables/useFeedback';
import { listAdminCouriers, listAdminOrders } from '~/domains/admin/api';

useAppSeoMeta({
  title: 'Логистика — админка BedDrop',
  description: 'Административные настройки логистики BedDrop: тарифы, маршруты, проверка адресов и активные доставки.',
  robots: 'noindex,nofollow',
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
const activeCouriers = ref<any[]>([]);
const activeOrders = ref<any[]>([]);
const selectedCourier = ref<any | null>(null);

const selectedCourierLocation = computed(() => selectedCourier.value?.latest_location || null);

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

const fetchOperationalSnapshot = async () => {
  const [couriers, orders] = await Promise.all([
    listAdminCouriers({ status: 'ACTIVE', per_page: 50 }),
    listAdminOrders({ per_page: 50 }),
  ]);

  activeCouriers.value = couriers.items.filter((courier: any) => courier.open_shift);
  activeOrders.value = orders.items.filter((order: any) => ['ACCEPTED_BY_RESTAURANT', 'READY_FOR_PICKUP', 'COURIER_ASSIGNED', 'PICKED_UP'].includes(order.status));
  selectedCourier.value = activeCouriers.value.find((courier: any) => courier.latest_location) || null;
};

onMounted(() => {
  fetchSettings();
  fetchOperationalSnapshot();
});
</script>

<template>
  <AdminShell>
    <div class="admin-logistics">
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

      <div class="admin-logistics__stack">
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
                  class="admin-logistics__field"
                >
                  <span class="admin-logistics__field-copy">
                    <span class="admin-logistics__field-label">{{ setting.label }}</span>
                    <small v-if="setting.description" class="admin-logistics__hint">
                      {{ setting.description }}
                    </small>
                    <code>{{ setting.key }}</code>
                  </span>
                  <span class="admin-logistics__field-control">
                    <BaseSwitch
                      v-if="setting.type === 'boolean'"
                      v-model="setting.value"
                      true-value="1"
                      false-value="0"
                    >
                      {{ setting.value === '1' ? 'Включено' : 'Отключено' }}
                    </BaseSwitch>
                    <input
                      v-else
                      v-model="setting.value"
                      :type="inputType(setting.type)"
                      :step="inputStep(setting.type)"
                      class="field-input"
                    >
                  </span>
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

        <section class="admin-logistics__panel surface-card">
          <div class="section-head">
            <h2 class="section-title">
              <MapPinned class="ui-icon" :size="19" :stroke-width="1.9" aria-hidden="true" />
              Операционная карта
            </h2>
          </div>

          <div class="admin-logistics__ops-grid">
            <div class="admin-logistics__map-area">
              <RouteMap
                v-if="selectedCourierLocation"
                :courier-location="selectedCourierLocation"
                :height="320"
              />
              <div v-else class="state-message state-message--empty">
                Нет активных курьеров с координатами.
              </div>
            </div>

            <div class="admin-logistics__ops-side">
              <div class="admin-logistics__mini-section">
                <h3>Активные курьеры</h3>
                <div class="admin-logistics__mini-list">
                  <button
                    v-for="courier in activeCouriers"
                    :key="courier.user_id"
                    type="button"
                    class="admin-logistics__mini-item"
                    :class="{ 'admin-logistics__mini-item--active': selectedCourier?.user_id === courier.user_id }"
                    @click="selectedCourier = courier"
                  >
                    <strong>{{ courier.user?.name || courier.user?.email || `Курьер #${courier.user_id}` }}</strong>
                    <span>{{ courier.latest_location ? `${courier.latest_location.lat}, ${courier.latest_location.lng}` : 'Координат нет' }}</span>
                  </button>
                  <div v-if="!activeCouriers.length" class="state-message state-message--empty">
                    Нет открытых смен.
                  </div>
                </div>
              </div>

              <div class="admin-logistics__mini-section">
                <h3>Активные заказы</h3>
                <div class="admin-logistics__mini-list">
                  <span v-for="order in activeOrders" :key="order.id" class="admin-logistics__mini-order">
                    #{{ order.id }} · {{ order.restaurant?.name || 'Ресторан' }} · {{ order.status }}
                  </span>
                  <div v-if="!activeOrders.length" class="state-message state-message--empty">
                    Нет активных заказов.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="admin-logistics__tools-grid">
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
              <select v-model="routeForm.mode" class="field-select">
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
        </section>

        <section class="admin-logistics__panel surface-card">
          <div class="section-head">
            <h2 class="section-title">
              <MapPinned class="ui-icon" :size="19" :stroke-width="1.9" aria-hidden="true" />
              Маршруты заказа
            </h2>
          </div>

          <div class="admin-logistics__inline-form admin-logistics__inline-form--wide">
            <input v-model="orderId" type="number" min="1" step="1" class="field-input" placeholder="ID заказа">
            <button type="button" class="button" :disabled="debugLoading" @click="doFetchOrderRoutes">
              Открыть
            </button>
          </div>

          <RouteMap
            v-if="orderRoutesResult?.route_segments?.length"
            :route-segments="orderRoutesResult.route_segments"
            :height="320"
          />
          <pre v-if="orderRoutesResult" class="admin-logistics__json">{{ JSON.stringify(orderRoutesResult, null, 2) }}</pre>
        </section>
      </div>
    </div>
  </AdminShell>
</template>

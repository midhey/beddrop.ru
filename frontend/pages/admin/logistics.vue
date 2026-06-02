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
      <div class="admin__head">
        <div>
          <h1 class="page-title">Логистика</h1>
          <p class="page-subtitle">
            Настройки доставки, проверка DaData и Valhalla, мониторинг маршрутов заказов.
          </p>
        </div>
      </div>

      <p v-if="errorMessage" class="state-message state-message--error">
        {{ errorMessage }}
      </p>

      <div class="admin-logistics__stack">
        <section class="admin__panel admin-logistics__panel">
          <div class="section-head">
            <div class="section-title-wrap">
              <div class="section-icon">
                <Settings2 class="ui-icon" :size="18" />
              </div>
              <h2 class="section-title">Настройки логистики</h2>
            </div>
            <div class="section-meta">
              Параметры стоимости и времени
            </div>
          </div>

          <div v-if="loading" class="state-message state-message--loading">
            Загружаем системные настройки...
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
                  <div class="admin-logistics__field-copy">
                    <span class="admin-logistics__field-label">{{ setting.label }}</span>
                    <small v-if="setting.description" class="admin-logistics__hint">
                      {{ setting.description }}
                    </small>
                    <code class="admin-logistics__field-key">{{ setting.key }}</code>
                  </div>
                  <div class="admin-logistics__field-control">
                    <BaseSwitch
                      v-if="setting.type === 'boolean'"
                      v-model="setting.value"
                      true-value="1"
                      false-value="0"
                    />
                    <input
                      v-else
                      v-model="setting.value"
                      :type="inputType(setting.type)"
                      :step="inputStep(setting.type)"
                      class="field-input"
                    >
                  </div>
                </label>
              </div>
            </div>

            <div class="admin-logistics__actions">
              <button
                type="button"
                class="button"
                :disabled="saving"
                @click="doSaveSettings"
              >
                {{ saving ? 'Сохраняем...' : 'Сохранить настройки' }}
              </button>
            </div>
          </div>
        </section>

        <section class="admin__panel admin-logistics__panel">
          <div class="section-head">
            <div class="section-title-wrap">
              <div class="section-icon">
                <MapPinned class="ui-icon" :size="18" />
              </div>
              <h2 class="section-title">Операционная карта</h2>
            </div>
            <div v-if="activeCouriers.length" class="section-meta">
              <strong>{{ activeCouriers.length }}</strong> курьеров онлайн
            </div>
          </div>

          <div class="admin-logistics__ops">
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
                    <div class="admin-logistics__mini-item-head">
                      <strong>{{ courier.user?.name || courier.user?.email || `Курьер #${courier.user_id}` }}</strong>
                      <span v-if="courier.latest_location" class="status-dot status-dot--success"></span>
                    </div>
                    <span>{{ courier.latest_location ? `${courier.latest_location.lat.toFixed(5)}, ${courier.latest_location.lng.toFixed(5)}` : 'Координат нет' }}</span>
                  </button>
                  <div v-if="!activeCouriers.length" class="state-message state-message--empty">
                    Нет открытых смен.
                  </div>
                </div>
              </div>

              <div class="admin-logistics__mini-section">
                <h3>Активные заказы</h3>
                <div class="admin-logistics__mini-list">
                  <div v-for="order in activeOrders" :key="order.id" class="admin-logistics__mini-order">
                    <strong>Заказ #{{ order.id }}</strong>
                    <span>{{ order.restaurant?.name || 'Ресторан' }} · {{ order.status }}</span>
                  </div>
                  <div v-if="!activeOrders.length" class="state-message state-message--empty">
                    Нет активных заказов.
                  </div>
                </div>
              </div>
            </div>

            <div class="admin-logistics__map-area">
              <RouteMap
                v-if="selectedCourierLocation"
                :courier-location="selectedCourierLocation"
                :height="400"
              />
              <div v-else class="admin-logistics__map-placeholder">
                <MapPinned :size="32" class="ui-icon" />
                <p>Выберите активного курьера для просмотра на карте</p>
              </div>
            </div>
          </div>
        </section>

        <div class="admin-logistics__tools-row">
          <section class="admin__panel admin-logistics__panel">
            <div class="section-head">
              <div class="section-title-wrap">
                <div class="section-icon">
                  <Search class="ui-icon" :size="18" />
                </div>
                <h2 class="section-title">DaData</h2>
              </div>
            </div>

            <div class="admin-logistics__inline-form">
              <input v-model="addressQuery" type="text" class="field-input" placeholder="Адрес для проверки">
              <button type="button" class="button" :disabled="debugLoading" @click="doTestAddress">
                Проверить
              </button>
            </div>

            <pre v-if="addressDebugResult" class="admin-logistics__json">{{ JSON.stringify(addressDebugResult, null, 2) }}</pre>
          </section>

          <section class="admin__panel admin-logistics__panel">
            <div class="section-head">
              <div class="section-title-wrap">
                <div class="section-icon">
                  <Route class="ui-icon" :size="18" />
                </div>
                <h2 class="section-title">Valhalla</h2>
              </div>
            </div>

            <div class="admin-logistics__route-form">
              <div class="admin-logistics__route-inputs">
                <input v-model="routeForm.fromLat" type="number" step="0.000001" class="field-input" placeholder="От lat">
                <input v-model="routeForm.fromLng" type="number" step="0.000001" class="field-input" placeholder="От lng">
                <input v-model="routeForm.toLat" type="number" step="0.000001" class="field-input" placeholder="До lat">
                <input v-model="routeForm.toLng" type="number" step="0.000001" class="field-input" placeholder="До lng">
              </div>
              <div class="admin-logistics__route-actions">
                <select v-model="routeForm.mode" class="field-select">
                  <option value="auto">auto</option>
                  <option value="bicycle">bicycle</option>
                  <option value="pedestrian">pedestrian</option>
                </select>
                <button type="button" class="button" :disabled="debugLoading" @click="doTestRoute">
                  Построить
                </button>
              </div>
            </div>

            <div v-if="routeDebugResult" class="admin-logistics__route-result">
              <div class="admin-logistics__route-stats">
                <strong>{{ (routeDebugResult.distance_meters / 1000).toFixed(2) }} км</strong>
                <span>{{ Math.ceil(routeDebugResult.duration_seconds / 60) }} мин</span>
              </div>
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
        </div>

        <section class="admin__panel admin-logistics__panel">
          <div class="section-head">
            <div class="section-title-wrap">
              <div class="section-icon">
                <Route class="ui-icon" :size="18" />
              </div>
              <h2 class="section-title">Маршруты заказа</h2>
            </div>
            <div class="section-meta">Анализ сегментов доставки</div>
          </div>

          <div class="admin-logistics__inline-form admin-logistics__inline-form--wide">
            <input v-model="orderId" type="number" min="1" step="1" class="field-input" placeholder="ID заказа">
            <button type="button" class="button" :disabled="debugLoading" @click="doFetchOrderRoutes">
              Открыть маршрут
            </button>
          </div>

          <div v-if="orderRoutesResult" class="admin-logistics__order-routes">
            <RouteMap
              v-if="orderRoutesResult.route_segments?.length"
              :route-segments="orderRoutesResult.route_segments"
              :height="400"
            />
            <pre class="admin-logistics__json">{{ JSON.stringify(orderRoutesResult, null, 2) }}</pre>
          </div>
        </section>
      </div>
    </div>
  </AdminShell>
</template>

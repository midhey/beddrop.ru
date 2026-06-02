<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { Bike } from 'lucide-vue-next';
import AdminShell from '~/components/admin/AdminShell.vue';
import { useAdminCouriers } from '~/composables/useAdmin';
import { updateAdminCourier } from '~/domains/admin/api';
import { formatDateTime } from '~/utils/formatting';

useAppSeoMeta({
  title: 'Курьеры — админка BedDrop',
  description: 'Административный раздел курьеров BedDrop: профили, смены, транспорт, статусы и последние координаты.',
  robots: 'noindex,nofollow',
});

const { items, loading, errorMessage, fetchItems } = useAdminCouriers();
const filters = reactive({
  search: '',
  status: '',
  vehicle: '',
});
const savingId = ref<number | null>(null);

const fetchCouriers = () => {
  const params: Record<string, any> = {};
  
  if (filters.search?.trim()) {
    params.search = filters.search.trim();
  }
  
  if (filters.status !== '') {
    params.status = filters.status;
  }
  
  if (filters.vehicle !== '') {
    params.vehicle = filters.vehicle;
  }
  
  fetchItems(params);
};

const updateCourier = async (userId: number, payload: Record<string, any>) => {
  savingId.value = userId;
  try {
    await updateAdminCourier(userId, payload);
    // Refresh only the affected item or full list
    await fetchCouriers();
  } finally {
    savingId.value = null;
  }
};

const selectValue = (event: Event) => (event.target as HTMLSelectElement).value;

const getUserInitials = (name: string | null, email: string) => {
  const base = name || email;
  return base.split(/[ @._]/).map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

onMounted(fetchCouriers);
</script>

<template>
  <AdminShell>
    <div class="admin__head">
      <div>
        <h1 class="page-title">Курьеры</h1>
        <p class="page-subtitle">Профили, смены, транспорт и последние координаты доставки.</p>
      </div>
    </div>

    <div class="admin-filters-wrap">
      <form class="admin-filters" @submit.prevent="fetchCouriers">
        <div class="admin-filters__main">
          <input 
            v-model="filters.search" 
            class="field-input" 
            placeholder="ID, email, телефон или имя..."
            @keyup.enter="fetchCouriers"
          >
        </div>
        <select v-model="filters.status" class="field-select">
          <option value="">Любой статус</option>
          <option value="ACTIVE">Активные</option>
          <option value="SUSPENDED">Приостановленные</option>
        </select>
        <select v-model="filters.vehicle" class="field-select">
          <option value="">Любой транспорт</option>
          <option value="FOOT">Пешком</option>
          <option value="BIKE">Велосипед</option>
          <option value="SCOOTER">Скутер</option>
          <option value="CAR">Авто</option>
        </select>
        <button class="button" type="submit">Поиск</button>
      </form>
    </div>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>

    <section class="admin__panel admin__panel--table">
      <div class="section-head">
        <div class="section-title-wrap">
          <div class="section-icon">
            <Bike class="ui-icon" :size="18" />
          </div>
          <h2 class="section-title">Список курьеров</h2>
        </div>
        <div v-if="pagination" class="section-meta">
          Всего: <strong>{{ pagination.total }}</strong>
        </div>
      </div>

      <div v-if="loading && !items.length" class="state-message state-message--loading">
        Загружаем список курьеров...
      </div>

      <div v-else-if="!items.length" class="state-message state-message--empty">
        Курьеры не найдены.
      </div>

      <div v-else class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th class="w-10">ID</th>
              <th>Курьер</th>
              <th>Статус</th>
              <th>Транспорт</th>
              <th class="text-center">Смена</th>
              <th>Координаты</th>
              <th class="text-center">Заказы</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="courier in items" :key="courier.user_id">
              <td class="admin-table__id">#{{ courier.user_id }}</td>
              <td>
                <div class="admin-table__user">
                  <div class="admin-table__avatar">
                    {{ getUserInitials(courier.user?.name || null, courier.user?.email || 'C') }}
                  </div>
                  <div class="admin-table__user-info">
                    <strong>{{ courier.user?.name || courier.user?.email || 'Без имени' }}</strong>
                    <span>{{ courier.user?.phone || courier.user?.email }}</span>
                  </div>
                </div>
              </td>
              <td>
                <select
                  class="field-select admin-table__control"
                  :value="courier.status"
                  :disabled="savingId === courier.user_id"
                  @change="updateCourier(courier.user_id, { status: selectValue($event) })"
                >
                  <option value="ACTIVE">ACTIVE</option>
                  <option value="SUSPENDED">SUSPENDED</option>
                </select>
              </td>
              <td>
                <select
                  class="field-select admin-table__control"
                  :value="courier.vehicle || ''"
                  :disabled="savingId === courier.user_id"
                  @change="updateCourier(courier.user_id, { vehicle: selectValue($event) || null })"
                >
                  <option value="">Не задан</option>
                  <option value="FOOT">FOOT</option>
                  <option value="BIKE">BIKE</option>
                  <option value="SCOOTER">SCOOTER</option>
                  <option value="CAR">CAR</option>
                </select>
              </td>
              <td class="text-center">
                <span 
                  class="status-chip" 
                  :class="courier.open_shift ? 'status-chip--success' : 'status-chip--muted'"
                >
                  {{ courier.open_shift ? 'В работе' : 'Оффлайн' }}
                </span>
              </td>
              <td>
                <span v-if="courier.latest_location" class="admin-table__date">
                  {{ courier.latest_location.lat.toFixed(4) }}, {{ courier.latest_location.lng.toFixed(4) }}
                </span>
                <span v-else class="admin-table__date">Нет данных</span>
              </td>
              <td class="text-center">
                <span class="admin-table__count" :class="{ 'admin-table__count--zero': !courier.orders_count }">
                  {{ courier.orders_count ?? 0 }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminShell>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import AdminShell from '~/components/admin/AdminShell.vue';
import { useAdminCouriers } from '~/composables/useAdmin';
import { updateAdminCourier } from '~/domains/admin/api';
import { formatDateTime } from '~/utils/formatting';

useSeoMeta({ title: 'Курьеры — админка BedDrop' });

const { items, loading, errorMessage, fetchItems } = useAdminCouriers();
const filters = reactive({
  search: '',
  status: '',
  vehicle: '',
});
const savingId = ref<number | null>(null);

const fetchCouriers = () => fetchItems({ ...filters });

const updateCourier = async (userId: number, payload: Record<string, any>) => {
  savingId.value = userId;
  try {
    await updateAdminCourier(userId, payload);
    await fetchCouriers();
  } finally {
    savingId.value = null;
  }
};

const selectValue = (event: Event) => (event.target as HTMLSelectElement).value;

onMounted(fetchCouriers);
</script>

<template>
  <AdminShell>
    <div class="admin__head">
      <div>
        <h1 class="page-title">Курьеры</h1>
        <p class="page-subtitle">Профили, смены, транспорт и последние координаты.</p>
      </div>
    </div>

    <form class="admin-filters" @submit.prevent="fetchCouriers">
      <input v-model="filters.search" class="field-input" placeholder="ID, email, телефон, имя">
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
      <button class="button" type="submit">Фильтровать</button>
    </form>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>
    <section class="admin__panel">
      <div v-if="loading" class="state-message state-message--loading">Загружаем курьеров...</div>
      <table v-else class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Курьер</th>
            <th>Статус</th>
            <th>Транспорт</th>
            <th>Смена</th>
            <th>Координаты</th>
            <th>Заказы</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="courier in items" :key="courier.user_id">
            <td>#{{ courier.user_id }}</td>
            <td>
              <strong>{{ courier.user?.name || courier.user?.email }}</strong>
              <span>{{ courier.user?.phone }}</span>
            </td>
            <td>
              <select
                class="field-input admin-table__control"
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
                class="field-input admin-table__control"
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
            <td>{{ courier.open_shift ? 'Открыта' : 'Нет' }}</td>
            <td>
              <span v-if="courier.latest_location">
                {{ courier.latest_location.lat }}, {{ courier.latest_location.lng }}
              </span>
              <span v-else>Нет данных</span>
            </td>
            <td>{{ courier.orders_count ?? 0 }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </AdminShell>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import AdminShell from '~/components/admin/AdminShell.vue';
import BaseSwitch from '~/components/ui/BaseSwitch.vue';
import { useAdminRestaurants } from '~/composables/useAdmin';
import type { Restaurant } from '~/composables/useRestaurants';
import { getAdminRestaurant, updateAdminRestaurant } from '~/domains/admin/api';
import { formatDateTime } from '~/utils/formatting';

useAppSeoMeta({
  title: 'Рестораны — админка BedDrop',
  description: 'Административный раздел ресторанов BedDrop: поиск, статусы активности, приём заказов и настройки заведений.',
  robots: 'noindex,nofollow',
});

const { items, loading, errorMessage, fetchItems } = useAdminRestaurants();
const selected = ref<any | null>(null);
const edit = reactive<Record<string, any>>({});
const filters = reactive({
  search: '',
  is_active: '',
  accepts_orders: '',
});

const fetchRestaurants = async () => {
  const response = await fetchItems({ ...filters });

  if (response.items.length && !selected.value) {
    await openRestaurant(response.items[0]);
  }

  if (!response.items.length) {
    selected.value = null;
  }
};

const openRestaurant = async (restaurant: Restaurant) => {
  selected.value = await getAdminRestaurant(restaurant.id);
  Object.assign(edit, selected.value.restaurant);
};

const saveRestaurant = async () => {
  if (!selected.value?.restaurant?.id) return;
  await updateAdminRestaurant(selected.value.restaurant.id, {
    name: edit.name,
    slug: edit.slug,
    phone: edit.phone,
    is_active: edit.is_active,
    accepts_orders: edit.accepts_orders,
    timezone: edit.timezone,
    opens_at: edit.opens_at,
    closes_at: edit.closes_at,
    closed_reason: edit.closed_reason,
    prep_time_min: edit.prep_time_min,
    prep_time_max: edit.prep_time_max,
  });
  await fetchRestaurants();
  selected.value = await getAdminRestaurant(selected.value.restaurant.id);
};

onMounted(fetchRestaurants);
</script>

<template>
  <AdminShell>
    <div class="admin__head">
      <div>
        <h1 class="page-title">Рестораны</h1>
        <p class="page-subtitle">Активность, прием заказов и операционные настройки.</p>
      </div>
    </div>

    <form class="admin-filters" @submit.prevent="fetchRestaurants">
      <input v-model="filters.search" class="field-input" placeholder="Название, slug, телефон">
      <select v-model="filters.is_active" class="field-select">
        <option value="">Любая активность</option>
        <option value="1">Активные</option>
        <option value="0">Отключенные</option>
      </select>
      <select v-model="filters.accepts_orders" class="field-select">
        <option value="">Любой прием</option>
        <option value="1">Принимают заказы</option>
        <option value="0">Не принимают</option>
      </select>
      <button class="button" type="submit">Фильтровать</button>
    </form>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>

    <div class="admin__split">
      <section class="admin__panel">
        <div v-if="loading" class="state-message state-message--loading">Загружаем рестораны...</div>
        <div v-if="!loading && !items.length" class="state-message state-message--empty">
          Рестораны не найдены. Проверьте фильтры или наличие данных в базе.
        </div>
        <table v-else-if="!loading" class="admin-table admin-table--selectable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Ресторан</th>
              <th>Активен</th>
              <th>Прием</th>
              <th>Заказы</th>
              <th>Создан</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="restaurant in items"
              :key="restaurant.id"
              :class="{ 'admin-table__row--active': selected?.restaurant?.id === restaurant.id }"
              @click="openRestaurant(restaurant)"
            >
              <td>#{{ restaurant.id }}</td>
              <td>
                <strong>{{ restaurant.name }}</strong>
                <span>{{ restaurant.slug }}</span>
              </td>
              <td>{{ restaurant.is_active ? 'Да' : 'Нет' }}</td>
              <td>{{ restaurant.accepts_orders ? 'Да' : 'Нет' }}</td>
              <td>{{ (restaurant as any).orders_count ?? 0 }}</td>
              <td>{{ formatDateTime(restaurant.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <aside class="admin__panel admin__details">
        <div v-if="!selected" class="state-message state-message--empty">Выберите ресторан из таблицы.</div>
        <template v-else>
          <div class="section-head">
            <h2 class="section-title">{{ selected.restaurant.name }}</h2>
          </div>
          <div class="admin-form">
            <label class="form-field"><span class="form-field__label">Название</span><input v-model="edit.name" class="field-input"></label>
            <label class="form-field"><span class="form-field__label">Slug</span><input v-model="edit.slug" class="field-input"></label>
            <label class="form-field"><span class="form-field__label">Телефон</span><input v-model="edit.phone" class="field-input"></label>
            <label class="form-field"><span class="form-field__label">Часовой пояс</span><input v-model="edit.timezone" class="field-input"></label>
            <label class="form-field"><span class="form-field__label">Открытие</span><input v-model="edit.opens_at" class="field-input" type="time"></label>
            <label class="form-field"><span class="form-field__label">Закрытие</span><input v-model="edit.closes_at" class="field-input" type="time"></label>
            <label class="form-field"><span class="form-field__label">Причина закрытия</span><input v-model="edit.closed_reason" class="field-input"></label>
            <BaseSwitch
              v-model="edit.is_active"
              class="base-switch--boxed"
            >
              Ресторан активен
            </BaseSwitch>
            <BaseSwitch
              v-model="edit.accepts_orders"
              class="base-switch--boxed"
            >
              Принимает новые заказы
            </BaseSwitch>
            <button class="button" type="button" @click="saveRestaurant">Сохранить</button>
          </div>
        </template>
      </aside>
    </div>
  </AdminShell>
</template>

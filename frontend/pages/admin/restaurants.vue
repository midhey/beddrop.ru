<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { Store } from 'lucide-vue-next';
import AdminShell from '~/components/admin/AdminShell.vue';
import BaseSwitch from '~/components/ui/BaseSwitch.vue';
import BaseModal from '~/components/ui/BaseModal.vue';
import { useAdminRestaurants } from '~/composables/useAdmin';
import type { Restaurant } from '~/composables/useRestaurants';
import { getAdminRestaurant, updateAdminRestaurant } from '~/domains/admin/api';
import { formatDateTime } from '~/utils/formatting';

useAppSeoMeta({
  title: 'Рестораны — админка BedDrop',
  description: 'Административный раздел ресторанов BedDrop: поиск, статусы активности, приём заказов и настройки заведений.',
  robots: 'noindex,nofollow',
});

const { items, pagination, loading, errorMessage, fetchItems } = useAdminRestaurants();
const selected = ref<any | null>(null);
const isModalOpen = ref(false);
const edit = reactive<Record<string, any>>({});
const filters = reactive({
  search: '',
  is_active: '',
  accepts_orders: '',
});

const fetchRestaurants = () => {
  const params: Record<string, any> = {};
  
  if (filters.search?.trim()) {
    params.search = filters.search.trim();
  }
  
  if (filters.is_active !== '') {
    params.is_active = filters.is_active;
  }
  
  if (filters.accepts_orders !== '') {
    params.accepts_orders = filters.accepts_orders;
  }
  
  fetchItems(params);
};

const openRestaurant = async (restaurant: Restaurant) => {
  selected.value = await getAdminRestaurant(restaurant.id);
  Object.assign(edit, selected.value.restaurant);
  isModalOpen.value = true;
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
  isModalOpen.value = false;
};

const getRestaurantInitial = (name?: string | null) => name ? name.charAt(0).toUpperCase() : 'R';

onMounted(fetchRestaurants);
</script>

<template>
  <AdminShell>
    <div class="admin__head">
      <div>
        <h1 class="page-title">Рестораны</h1>
        <p class="page-subtitle">Активность, прием заказов и операционные настройки заведений.</p>
      </div>
    </div>

    <div class="admin-filters-wrap">
      <form class="admin-filters" @submit.prevent="fetchRestaurants">
        <div class="admin-filters__main">
          <input 
            v-model="filters.search" 
            class="field-input" 
            placeholder="Название, slug или телефон..."
            @keyup.enter="fetchRestaurants"
          >
        </div>
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
        <button class="button" type="submit">Поиск</button>
      </form>
    </div>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>

    <section class="admin__panel admin__panel--table">
      <div class="section-head">
        <div class="section-title-wrap">
          <div class="section-icon">
            <Store class="ui-icon" :size="18" />
          </div>
          <h2 class="section-title">Список ресторанов</h2>
        </div>
        <div v-if="pagination" class="section-meta">
          Всего: <strong>{{ pagination.total }}</strong>
        </div>
      </div>

      <div v-if="loading && !items.length" class="state-message state-message--loading">
        Загружаем рестораны...
      </div>
      
      <div v-else-if="!items.length" class="state-message state-message--empty">
        Рестораны не найдены.
      </div>

      <div v-else class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th class="w-10">ID</th>
              <th>Ресторан</th>
              <th class="text-center">Активен</th>
              <th class="text-center">Прием</th>
              <th class="text-center">Заказы</th>
              <th>Создан</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="restaurant in items"
              :key="restaurant.id"
              @click="openRestaurant(restaurant)"
            >
              <td class="admin-table__id">#{{ restaurant.id }}</td>
              <td>
                <div class="admin-table__user">
                  <div class="admin-table__avatar">
                    {{ getRestaurantInitial(restaurant.name) }}
                  </div>
                  <div class="admin-table__user-info">
                    <strong>{{ restaurant.name }}</strong>
                    <span>{{ restaurant.slug }}</span>
                  </div>
                </div>
              </td>
              <td class="text-center">
                <span 
                  class="status-chip" 
                  :class="restaurant.is_active ? 'status-chip--success' : 'status-chip--danger'"
                >
                  {{ restaurant.is_active ? 'Активен' : 'Отключен' }}
                </span>
              </td>
              <td class="text-center">
                <span 
                  class="status-chip" 
                  :class="restaurant.accepts_orders ? 'status-chip--success' : 'status-chip--muted'"
                >
                  {{ restaurant.accepts_orders ? 'Принимает' : 'Пауза' }}
                </span>
              </td>
              <td class="text-center">
                <span class="admin-table__count" :class="{ 'admin-table__count--zero': !(restaurant as any).orders_count }">
                  {{ (restaurant as any).orders_count ?? 0 }}
                </span>
              </td>
              <td class="admin-table__date">
                {{ formatDateTime(restaurant.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <BaseModal v-model="isModalOpen" :title="edit.name || 'Настройки ресторана'" size="medium">
      <div class="admin__details">
        <div class="admin-form">
          <div class="admin__grid admin__grid--two">
            <label class="form-field"><span class="form-field__label">Название</span><input v-model="edit.name" class="field-input"></label>
            <label class="form-field"><span class="form-field__label">Slug</span><input v-model="edit.slug" class="field-input"></label>
          </div>
          
          <div class="admin__grid admin__grid--two">
            <label class="form-field"><span class="form-field__label">Телефон</span><input v-model="edit.phone" class="field-input"></label>
            <label class="form-field"><span class="form-field__label">Часовой пояс</span><input v-model="edit.timezone" class="field-input"></label>
          </div>

          <div class="admin__grid admin__grid--two">
            <label class="form-field"><span class="form-field__label">Открытие</span><input v-model="edit.opens_at" class="field-input" type="time"></label>
            <label class="form-field"><span class="form-field__label">Закрытие</span><input v-model="edit.closes_at" class="field-input" type="time"></label>
          </div>
          
          <label class="form-field"><span class="form-field__label">Причина закрытия (если на паузе)</span><input v-model="edit.closed_reason" class="field-input"></label>
          
          <div class="admin__grid admin__grid--two">
            <BaseSwitch v-model="edit.is_active" class="base-switch--boxed">
              Ресторан активен
            </BaseSwitch>
            
            <BaseSwitch v-model="edit.accepts_orders" class="base-switch--boxed">
              Принимает заказы
            </BaseSwitch>
          </div>
          
          <div class="form-actions">
            <button class="button" type="button" @click="saveRestaurant">Сохранить изменения</button>
          </div>
        </div>
      </div>
    </BaseModal>
  </AdminShell>
</template>

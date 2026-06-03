<script setup lang="ts">
import { onMounted, reactive, watch } from 'vue';
import { Users } from 'lucide-vue-next';
import AdminShell from '~/components/admin/AdminShell.vue';
import BaseSwitch from '~/components/ui/BaseSwitch.vue';
import { useAdminUsers } from '~/composables/useAdmin';
import { updateAdminUser } from '~/domains/admin/api';
import { formatDateTime } from '~/utils/formatting';

useAppSeoMeta({
  title: 'Пользователи — админка BedDrop',
  description: 'Административный раздел пользователей BedDrop: поиск аккаунтов, роли, блокировки и управление доступом.',
  robots: 'noindex,nofollow',
});

const { items, pagination, loading, errorMessage, fetchItems } = useAdminUsers();
const filters = reactive({
  search: '',
  is_admin: '',
  is_banned: '',
});

const fetchUsers = () => {
  const params: Record<string, any> = {};
  
  if (filters.search?.trim()) {
    params.search = filters.search.trim();
  }
  
  if (filters.is_admin !== '') {
    params.is_admin = filters.is_admin;
  }
  
  if (filters.is_banned !== '') {
    params.is_banned = filters.is_banned;
  }
  
  fetchItems(params);
};

// Auto-fetch when filters change (except search which is manual or on enter)
watch(() => [filters.is_admin, filters.is_banned], () => {
  fetchUsers();
});

const toggleFlag = async (id: number, flag: 'is_admin' | 'is_banned', value: boolean) => {
  const user = items.value.find(u => u.id === id);
  if (!user) return;
  
  const originalValue = (user as any)[flag];
  (user as any)[flag] = value;
  
  try {
    await updateAdminUser(id, { [flag]: value });
  } catch (e) {
    (user as any)[flag] = originalValue;
  }
};

const getUserInitials = (name: string | null) => {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

onMounted(fetchUsers);
</script>

<template>
  <AdminShell>
    <div class="admin__head">
      <div>
        <h1 class="page-title">Пользователи</h1>
        <p class="page-subtitle">Управление правами доступа и статусами аккаунтов клиентов.</p>
      </div>
    </div>

    <div class="admin-filters-wrap">
      <form class="admin-filters" @submit.prevent="fetchUsers">
        <div class="admin-filters__main">
          <input 
            v-model="filters.search" 
            class="field-input" 
            placeholder="ID, email, телефон или имя..."
            @keyup.enter="fetchUsers"
          >
        </div>
        <select v-model="filters.is_admin" class="field-select">
          <option value="">Все роли</option>
          <option value="1">Только админы</option>
          <option value="0">Без прав админа</option>
        </select>
        <select v-model="filters.is_banned" class="field-select">
          <option value="">Все статусы</option>
          <option value="1">Заблокированные</option>
          <option value="0">Активные</option>
        </select>
        <button class="button" type="submit">Поиск</button>
      </form>
    </div>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>

    <section class="admin__panel admin__panel--table">
      <div class="section-head">
        <div class="section-title-wrap">
          <div class="section-icon">
            <Users class="ui-icon" :size="18" />
          </div>
          <h2 class="section-title">База пользователей</h2>
        </div>
        <div v-if="pagination" class="section-meta">
          Всего: <strong>{{ pagination.total }}</strong>
        </div>
      </div>

      <div v-if="loading && !items.length" class="state-message state-message--loading">
        Загружаем список пользователей...
      </div>
      
      <div v-else-if="!items.length" class="state-message state-message--empty">
        Пользователи не найдены. Попробуйте изменить параметры фильтрации.
      </div>

      <div v-else class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th class="w-10">ID</th>
              <th>Пользователь</th>
              <th>Контакты</th>
              <th class="text-center">Заказы</th>
              <th class="text-center">Админ</th>
              <th class="text-center">Активен</th>
              <th>Регистрация</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in items" :key="user.id">
              <td class="admin-table__id">#{{ user.id }}</td>
              <td>
                <div class="admin-table__user">
                  <div class="admin-table__avatar">
                    {{ getUserInitials(user.name) }}
                  </div>
                  <div class="admin-table__user-info">
                    <strong>{{ user.name || 'Без имени' }}</strong>
                    <span>{{ user.email }}</span>
                  </div>
                </div>
              </td>
              <td class="admin-table__contacts">
                <span class="phone">{{ user.phone || '—' }}</span>
              </td>
              <td class="text-center">
                <span class="admin-table__count" :class="{ 'admin-table__count--zero': !user.orders_count }">
                  {{ user.orders_count ?? 0 }}
                </span>
              </td>
              <td class="text-center">
                <div class="admin-table__switch">
                  <BaseSwitch
                    :model-value="user.is_admin"
                    @update:model-value="v => toggleFlag(user.id, 'is_admin', !!v)"
                  />
                </div>
              </td>
              <td class="text-center">
                <div class="admin-table__switch">
                  <BaseSwitch
                    :model-value="!user.is_banned"
                    @update:model-value="v => toggleFlag(user.id, 'is_banned', !v)"
                  />
                </div>
              </td>
              <td class="admin-table__date">
                {{ formatDateTime(user.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminShell>
</template>

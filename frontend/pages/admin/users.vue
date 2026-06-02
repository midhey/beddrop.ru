<script setup lang="ts">
import { onMounted, reactive } from 'vue';
import AdminShell from '~/components/admin/AdminShell.vue';
import { useAdminUsers } from '~/composables/useAdmin';
import { updateAdminUser } from '~/domains/admin/api';
import { formatDateTime } from '~/utils/formatting';

useAppSeoMeta({
  title: 'Пользователи — админка BedDrop',
  description: 'Административный раздел пользователей BedDrop: поиск аккаунтов, роли, блокировки и управление доступом.',
  robots: 'noindex,nofollow',
});

const { items, loading, errorMessage, fetchItems } = useAdminUsers();
const filters = reactive({
  search: '',
  is_admin: '',
  is_banned: '',
});

const fetchUsers = () => fetchItems({ ...filters });

const toggleFlag = async (id: number, flag: 'is_admin' | 'is_banned', value: boolean) => {
  await updateAdminUser(id, { [flag]: value });
  await fetchUsers();
};

onMounted(fetchUsers);
</script>

<template>
  <AdminShell>
    <div class="admin__head">
      <div>
        <h1 class="page-title">Пользователи</h1>
        <p class="page-subtitle">Поиск, бан и административные права.</p>
      </div>
    </div>

    <form class="admin-filters" @submit.prevent="fetchUsers">
      <input v-model="filters.search" class="field-input" placeholder="ID, email, телефон, имя">
      <select v-model="filters.is_admin" class="field-select">
        <option value="">Любые права</option>
        <option value="1">Админы</option>
        <option value="0">Не админы</option>
      </select>
      <select v-model="filters.is_banned" class="field-select">
        <option value="">Любой статус</option>
        <option value="1">Забанены</option>
        <option value="0">Активны</option>
      </select>
      <button class="button" type="submit">Фильтровать</button>
    </form>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>
    <section class="admin__panel">
      <div v-if="loading" class="state-message state-message--loading">Загружаем пользователей...</div>
      <table v-else class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Пользователь</th>
            <th>Телефон</th>
            <th>Заказы</th>
            <th>Рестораны</th>
            <th>Права</th>
            <th>Статус</th>
            <th>Создан</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in items" :key="user.id">
            <td>#{{ user.id }}</td>
            <td>
              <strong>{{ user.name || user.email }}</strong>
              <span>{{ user.email }}</span>
            </td>
            <td>{{ user.phone }}</td>
            <td>{{ user.orders_count ?? 0 }}</td>
            <td>{{ user.restaurants_count ?? 0 }}</td>
            <td>
              <button class="admin-link" type="button" @click="toggleFlag(user.id, 'is_admin', !user.is_admin)">
                {{ user.is_admin ? 'Снять admin' : 'Сделать admin' }}
              </button>
            </td>
            <td>
              <button class="admin-link" type="button" @click="toggleFlag(user.id, 'is_banned', !user.is_banned)">
                {{ user.is_banned ? 'Разбанить' : 'Забанить' }}
              </button>
            </td>
            <td>{{ formatDateTime(user.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </AdminShell>
</template>

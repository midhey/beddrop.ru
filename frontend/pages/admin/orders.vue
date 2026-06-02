<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import AdminShell from '~/components/admin/AdminShell.vue';
import RouteMap from '~/components/map/RouteMap.vue';
import { useAdminOrders } from '~/composables/useAdmin';
import type { Order } from '~/composables/useOrders';
import {
  adminAcceptOrder,
  adminAssignCourier,
  adminCancelOrder,
  adminMarkDelivered,
  adminMarkPickedUp,
  adminMarkReady,
  adminUnassignCourier,
  adminUpdatePayment,
  getAdminOrder,
} from '~/domains/admin/api';
import {
  getOrderStatusClass,
  getOrderStatusLabel,
  getPaymentStatusLabel,
  sortOrderEvents,
} from '~/domains/orders/presentation';
import { formatDateTime, formatPrice } from '~/utils/formatting';

useAppSeoMeta({
  title: 'Заказы — админка BedDrop',
  description: 'Административный список заказов BedDrop: фильтры, статусы, курьеры, оплата и управление жизненным циклом заказа.',
  robots: 'noindex,nofollow',
});

const { items, loading, errorMessage, fetchItems } = useAdminOrders();
const selected = ref<Order | null>(null);
const selectedLoading = ref(false);
const actionLoading = ref(false);
const courierUserId = ref('');
const cancelReason = ref('');
const paymentStatus = ref('');
const filters = reactive({
  search: '',
  status: '',
  payment_status: '',
});

const fetchOrders = () => fetchItems({ ...filters });

const openOrder = async (order: Order) => {
  selectedLoading.value = true;
  selected.value = order;
  paymentStatus.value = order.payment_status;
  try {
    selected.value = await getAdminOrder(order.id);
    paymentStatus.value = selected.value.payment_status;
  } finally {
    selectedLoading.value = false;
  }
};

const runAction = async (action: () => Promise<Order>) => {
  if (!selected.value) return;
  actionLoading.value = true;
  try {
    selected.value = await action();
    paymentStatus.value = selected.value.payment_status;
    await fetchOrders();
  } finally {
    actionLoading.value = false;
  }
};

const orderStatuses = [
  'CREATED',
  'ACCEPTED_BY_RESTAURANT',
  'READY_FOR_PICKUP',
  'COURIER_ASSIGNED',
  'PICKED_UP',
  'DELIVERED',
  'CANCELED_BY_USER',
  'CANCELED_BY_RESTAURANT',
];

const paymentStatuses = ['PENDING', 'AUTHORIZED', 'PAID', 'REFUNDED', 'FAILED'];

onMounted(fetchOrders);
</script>

<template>
  <AdminShell>
    <div class="admin__head">
      <div>
        <h1 class="page-title">Заказы</h1>
        <p class="page-subtitle">Полная лента заказов, события, маршруты и ручные действия.</p>
      </div>
    </div>

    <form class="admin-filters" @submit.prevent="fetchOrders">
      <input v-model="filters.search" class="field-input" placeholder="ID, клиент, ресторан">
      <select v-model="filters.status" class="field-select">
        <option value="">Все статусы</option>
        <option v-for="status in orderStatuses" :key="status" :value="status">
          {{ getOrderStatusLabel(status, 'restaurant') }}
        </option>
      </select>
      <select v-model="filters.payment_status" class="field-select">
        <option value="">Любая оплата</option>
        <option v-for="status in paymentStatuses" :key="status" :value="status">
          {{ getPaymentStatusLabel(status) }}
        </option>
      </select>
      <button class="button" type="submit">Фильтровать</button>
    </form>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>

    <div class="admin__split">
      <section class="admin__panel">
        <div v-if="loading" class="state-message state-message--loading">Загружаем заказы...</div>
        <table v-else class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Статус</th>
              <th>Ресторан</th>
              <th>Клиент</th>
              <th>Сумма</th>
              <th>Создан</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in items"
              :key="order.id"
              :class="{ 'admin-table__row--active': selected?.id === order.id }"
              @click="openOrder(order)"
            >
              <td>#{{ order.id }}</td>
              <td><span class="order-status status-chip" :class="getOrderStatusClass(order.status)">{{ getOrderStatusLabel(order.status, 'restaurant') }}</span></td>
              <td>{{ order.restaurant?.name || 'Ресторан' }}</td>
              <td>{{ order.user?.email || `#${order.user_id}` }}</td>
              <td>{{ formatPrice(order.total_price) }}</td>
              <td>{{ formatDateTime(order.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <aside class="admin__panel admin__details">
        <div v-if="!selected" class="state-message state-message--empty">Выберите заказ из таблицы.</div>
        <div v-else-if="selectedLoading" class="state-message state-message--loading">Открываем заказ...</div>
        <template v-else>
          <div class="section-head">
            <h2 class="section-title">Заказ #{{ selected.id }}</h2>
            <span class="order-status status-chip" :class="getOrderStatusClass(selected.status)">
              {{ getOrderStatusLabel(selected.status, 'restaurant') }}
            </span>
          </div>

          <div class="admin__facts">
            <span>Клиент: <strong>{{ selected.user?.email || `#${selected.user_id}` }}</strong></span>
            <span>Курьер: <strong>{{ selected.courier?.user?.email || selected.courier_id || 'Не назначен' }}</strong></span>
            <span>Оплата: <strong>{{ getPaymentStatusLabel(selected.payment_status) }}</strong></span>
            <span>Сумма: <strong>{{ formatPrice(selected.total_price) }}</strong></span>
          </div>

          <div class="admin-actions">
            <button class="button" :disabled="actionLoading || selected.status !== 'CREATED'" @click="runAction(() => adminAcceptOrder(selected!.id))">Принять</button>
            <button class="button" :disabled="actionLoading || selected.status !== 'ACCEPTED_BY_RESTAURANT'" @click="runAction(() => adminMarkReady(selected!.id))">Готов к выдаче</button>
            <button class="button" :disabled="actionLoading || selected.status !== 'COURIER_ASSIGNED'" @click="runAction(() => adminMarkPickedUp(selected!.id))">У курьера</button>
            <button class="button" :disabled="actionLoading || selected.status !== 'PICKED_UP'" @click="runAction(() => adminMarkDelivered(selected!.id))">Доставлен</button>
          </div>

          <div class="admin-actions admin-actions--stack">
            <input v-model="courierUserId" class="field-input" type="number" min="1" placeholder="ID курьера">
            <button class="button" :disabled="actionLoading || !courierUserId || selected.status !== 'READY_FOR_PICKUP'" @click="runAction(() => adminAssignCourier(selected!.id, Number(courierUserId)))">Назначить</button>
            <button class="button button--ghost" :disabled="actionLoading || selected.status !== 'COURIER_ASSIGNED'" @click="runAction(() => adminUnassignCourier(selected!.id))">Снять курьера</button>
          </div>

          <div class="admin-actions admin-actions--stack">
            <input v-model="cancelReason" class="field-input" placeholder="Причина отмены">
            <button class="button button--danger" :disabled="actionLoading || ['DELIVERED','CANCELED_BY_USER','CANCELED_BY_RESTAURANT'].includes(selected.status)" @click="runAction(() => adminCancelOrder(selected!.id, cancelReason))">Отменить</button>
          </div>

          <div class="admin-actions admin-actions--stack">
            <select v-model="paymentStatus" class="field-select">
              <option v-for="status in paymentStatuses" :key="status" :value="status">{{ getPaymentStatusLabel(status) }}</option>
            </select>
            <button class="button button--ghost" :disabled="actionLoading || paymentStatus === selected.payment_status" @click="runAction(() => adminUpdatePayment(selected!.id, paymentStatus))">Обновить оплату</button>
          </div>

          <RouteMap
            v-if="selected.route_segments?.length"
            :route-segments="selected.route_segments"
            :restaurant-address="selected.restaurant?.address"
            :delivery-address="selected.delivery_address"
            :height="260"
          />

          <div class="admin__timeline">
            <h3>События</h3>
            <div v-for="event in sortOrderEvents(selected.events)" :key="event.id" class="admin__timeline-item">
              <span>{{ formatDateTime(event.created_at) }}</span>
              <strong>{{ getOrderStatusLabel(event.event, 'restaurant') }}</strong>
            </div>
          </div>
        </template>
      </aside>
    </div>
  </AdminShell>
</template>

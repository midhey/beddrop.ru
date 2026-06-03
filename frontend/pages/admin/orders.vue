<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { ReceiptText } from 'lucide-vue-next';
import AdminShell from '~/components/admin/AdminShell.vue';
import RouteMap from '~/components/map/RouteMap.vue';
import BaseModal from '~/components/ui/BaseModal.vue';
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

const { handleApiError } = useApiHelpers();
const { items, loading, errorMessage, fetchItems, pagination } = useAdminOrders();
const selected = ref<Order | null>(null);
const isModalOpen = ref(false);
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

const fetchOrders = () => {
  const params: Record<string, any> = {};
  
  if (filters.search?.trim()) {
    params.search = filters.search.trim();
  }
  
  if (filters.status !== '') {
    params.status = filters.status;
  }
  
  if (filters.payment_status !== '') {
    params.payment_status = filters.payment_status;
  }
  
  fetchItems(params);
};

// Auto-fetch when filters change (except search)
watch(() => [filters.status, filters.payment_status], () => {
  fetchOrders();
});

const openOrder = async (order: Order) => {
  selected.value = order;
  isModalOpen.value = true;
  selectedLoading.value = true;
  paymentStatus.value = order.payment_status;
  try {
    selected.value = await getAdminOrder(order.id);
    paymentStatus.value = selected.value.payment_status;
  } catch (e) {
    handleApiError(e);
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
  } catch (e) {
    handleApiError(e);
  } finally {
    actionLoading.value = false;
  }
};

const getRestaurantInitial = (name?: string) => name ? name.charAt(0).toUpperCase() : 'R';

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

    <div class="admin-filters-wrap">
      <form class="admin-filters" @submit.prevent="fetchOrders">
        <div class="admin-filters__main">
          <input 
            v-model="filters.search" 
            class="field-input" 
            placeholder="ID, клиент или ресторан..."
            @keyup.enter="fetchOrders"
          >
        </div>
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
        <button class="button" type="submit">Поиск</button>
      </form>
    </div>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>

    <section class="admin__panel admin__panel--table">
      <div class="section-head">
        <div class="section-title-wrap">
          <div class="section-icon">
            <ReceiptText class="ui-icon" :size="18" />
          </div>
          <h2 class="section-title">Список заказов</h2>
        </div>
        <div v-if="pagination" class="section-meta">
          Всего: <strong>{{ pagination.total }}</strong>
        </div>
      </div>

      <div v-if="loading && !items.length" class="state-message state-message--loading">
        Загружаем список заказов...
      </div>

      <div v-else-if="!items.length" class="state-message state-message--empty">
        Заказы не найдены.
      </div>

      <div v-else class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th class="w-10">ID</th>
              <th>Статус</th>
              <th>Ресторан</th>
              <th>Клиент</th>
              <th class="text-center">Сумма</th>
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
              <td class="admin-table__id">#{{ order.id }}</td>
              <td>
                <span class="order-status status-chip" :class="getOrderStatusClass(order.status)">
                  {{ getOrderStatusLabel(order.status, 'restaurant') }}
                </span>
              </td>
              <td>
                <div class="admin-table__user">
                  <div class="admin-table__avatar">
                    {{ getRestaurantInitial(order.restaurant?.name) }}
                  </div>
                  <div class="admin-table__user-info">
                    <strong>{{ order.restaurant?.name || 'Ресторан' }}</strong>
                    <span>{{ order.restaurant?.slug || '—' }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="admin-table__user-info">
                  <strong>{{ order.user?.name || 'Клиент' }}</strong>
                  <span>{{ order.user?.email || `#${order.user_id}` }}</span>
                </div>
              </td>
              <td class="text-center">
                <strong>{{ formatPrice(order.total_price) }}</strong>
              </td>
              <td class="admin-table__date">
                {{ formatDateTime(order.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <BaseModal v-model="isModalOpen" :title="selected ? `Заказ #${selected.id}` : 'Детали заказа'" size="large">
      <div v-if="selectedLoading" class="state-message state-message--loading">Открываем заказ...</div>
      <div v-else-if="selected" class="admin__details">
        <div class="section-head">
          <h2 class="section-title">Основная информация</h2>
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
          <button class="button" :disabled="actionLoading || !['CREATED', 'ACCEPTED_BY_RESTAURANT'].includes(selected.status)" @click="runAction(() => adminMarkReady(selected!.id))">Готов к выдаче</button>
          <button class="button" :disabled="actionLoading || !['CREATED', 'ACCEPTED_BY_RESTAURANT', 'READY_FOR_PICKUP', 'COURIER_ASSIGNED'].includes(selected.status)" @click="runAction(() => adminMarkPickedUp(selected!.id))">У курьера</button>
          <button class="button" :disabled="actionLoading || ['DELIVERED', 'CANCELED_BY_USER', 'CANCELED_BY_RESTAURANT'].includes(selected.status)" @click="runAction(() => adminMarkDelivered(selected!.id))">Доставлен</button>
        </div>

        <div class="admin-actions admin-actions--stack">
          <input v-model="courierUserId" class="field-input" type="number" min="1" placeholder="ID курьера">
          <button class="button" :disabled="actionLoading || !courierUserId || ['DELIVERED', 'CANCELED_BY_USER', 'CANCELED_BY_RESTAURANT'].includes(selected.status)" @click="runAction(() => adminAssignCourier(selected!.id, Number(courierUserId)))">Назначить</button>
          <button class="button button--ghost" :disabled="actionLoading || !selected.courier_id || ['DELIVERED', 'CANCELED_BY_USER', 'CANCELED_BY_RESTAURANT'].includes(selected.status)" @click="runAction(() => adminUnassignCourier(selected!.id))">Снять курьера</button>
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
          :height="320"
        />

        <div class="admin__timeline">
          <h3>События</h3>
          <div v-for="event in sortOrderEvents(selected.events)" :key="event.id" class="admin__timeline-item">
            <span>{{ formatDateTime(event.created_at) }}</span>
            <strong>{{ getOrderStatusLabel(event.event, 'restaurant') }}</strong>
          </div>
        </div>
      </div>
    </BaseModal>
  </AdminShell>
</template>

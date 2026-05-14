<script setup lang="ts">
import { ArrowLeft, MapPin, Clock, ShoppingBag } from 'lucide-vue-next';
import { useOrdersListPage } from '~/composables/useOrdersListPage';

const {
  items,
  loading,
  errorMessage,
  hasOrders,
  formatPrice,
  formatDateTime,
  getOrderStatusClass,
  getOrderStatusLabel,
  getPaymentStatusLabel,
} = useOrdersListPage();
</script>

<template>
  <section class="orders page-shell">
    <div class="orders__container container">
      <button
          type="button"
          class="orders__back page-back"
          @click="$router.back()"
      >
        <ArrowLeft class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
        <span>Назад</span>
      </button>

      <div class="page-head">
        <h1 class="orders__title page-title">
          Мои заказы
        </h1>
      </div>

      <div
          v-if="loading"
          class="orders__loading state-message state-message--loading"
      >
        Загрузка заказов...
      </div>

      <div
          v-else-if="errorMessage"
          class="orders__error state-message state-message--error"
      >
        {{ errorMessage }}
      </div>

      <div
          v-else-if="!hasOrders"
          class="orders__empty state-message state-message--empty"
      >
        У вас пока нет заказов.
        <div class="orders__empty-actions">
          <NuxtLink to="/" class="button">
            Найти ресторан
          </NuxtLink>
        </div>
      </div>

      <div
          v-else
          class="orders__list"
      >
        <NuxtLink
            v-for="order in items"
            :key="order.id"
            :to="`/orders/${order.id}`"
            class="orders__item surface-card"
        >
          <div class="orders__item-main">
            <div class="orders__item-top">
              <span class="orders__item-number">
                #{{ order.id }}
              </span>
              <span
                  class="order-status status-chip"
                  :class="getOrderStatusClass(order.status)"
              >
                {{ getOrderStatusLabel(order.status) }}
              </span>
            </div>

            <div class="orders__item-restaurant">
              {{ order.restaurant?.name || 'Ресторан' }}
            </div>

            <div class="orders__item-meta">
              <span>
                <ShoppingBag :size="12" class="ui-icon" />
                {{ order.items_count ?? '—' }} поз.
              </span>
              <span>
                <Clock :size="12" class="ui-icon" />
                {{ formatDateTime(order.created_at) }}
              </span>
            </div>
          </div>

          <div class="orders__item-right">
            <div class="orders__item-total">
              {{ formatPrice(order.total_price) }}
            </div>
            <div class="orders__item-pay">
              {{ getPaymentStatusLabel(order.payment_status) }}
            </div>
          </div>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

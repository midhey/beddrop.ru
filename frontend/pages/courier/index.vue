<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import { useCourierDashboardPage } from '~/composables/useCourierDashboardPage';

const {
  profile,
  shift,
  hasActiveShift,
  ordersBlockedByShift,
  loadingShift,
  errorMessage,
  availableOrders,
  activeOrders,
  historyOrders,
  pageLoading,
  actionOrderId,
  actionType,
  profileStatusLabel,
  vehicleLabel,
  ratingText,
  shiftSummary,
  formatPrice,
  formatDateTime,
  getCourierOrderStatusLabel,
  getRestaurantAddress,
  getDeliveryAddress,
  canCourierMarkPickedUp,
  canCourierMarkDelivered,
  startOrEndShift,
  doAssign,
  doPickup,
  doDeliver,
  goBack,
} = useCourierDashboardPage();
</script>

<template>
  <section class="courier page-shell">
    <div class="courier__container container">
      <button
          type="button"
          class="courier__back page-back"
          @click="goBack"
      >
        <ArrowLeft class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
        <span>Назад</span>
      </button>

      <div class="courier__header page-head">
        <h1 class="courier__title page-title">
          Курьерский кабинет
        </h1>

        <button
            type="button"
            class="button courier__shift-btn"
            :disabled="loadingShift"
            @click="startOrEndShift"
        >
          <template v-if="loadingShift">
            Обновляем смену...
          </template>
          <template v-else-if="hasActiveShift">
            Завершить смену
          </template>
          <template v-else>
            Начать смену
          </template>
        </button>
      </div>

      <p class="courier__subtitle page-subtitle">
        Откройте смену, чтобы видеть доступные заказы и брать их в работу. История доставок доступна всегда.
      </p>

      <div
          v-if="errorMessage"
          class="courier__error state-message state-message--error"
      >
        {{ errorMessage }}
      </div>

      <div
          v-if="pageLoading"
          class="courier__loading state-message state-message--loading"
      >
        Загружаем данные курьера...
      </div>

      <div
          v-else
          class="courier__layout"
      >
        <!-- Левая колонка -->
        <div class="courier__left">
          <!-- Профиль -->
          <section class="courier-card surface-card">
            <h2 class="courier-card__title section-title">
              Профиль курьера
            </h2>

            <div
                v-if="!profile"
                class="courier-card__empty state-message state-message--empty"
            >
              Профиль курьера не найден. Обратитесь к администратору.
            </div>

            <div
                v-else
                class="courier-profile"
            >
              <div class="courier-profile__row">
                <span class="courier-profile__label">
                  Статус
                </span>
                <span
                    class="courier-profile__value status-chip"
                    :class="{
                    'status-chip--success': profile.status === 'ACTIVE',
                    'status-chip--danger': profile.status === 'SUSPENDED',
                  }"
                >
                  {{ profileStatusLabel }}
                </span>
              </div>

              <div class="courier-profile__row">
                <span class="courier-profile__label">
                  Тип доставки
                </span>
                <span class="courier-profile__value">
                  {{ vehicleLabel }}
                </span>
              </div>

              <div class="courier-profile__row">
                <span class="courier-profile__label">
                  Рейтинг
                </span>
                <span class="courier-profile__value">
                  {{ ratingText }}
                </span>
              </div>

              <div class="courier-profile__row">
                <span class="courier-profile__label">
                  Смена
                </span>
                <span class="courier-profile__value">
                  {{ shiftSummary }}
                </span>
              </div>
            </div>
          </section>

          <!-- Доступные заказы -->
          <section class="courier-card surface-card">
            <div class="courier-card__header section-head">
              <h2 class="courier-card__title section-title">
                Доступные заказы
              </h2>
              <span class="courier-card__meta section-meta">
                {{ availableOrders.length }} шт.
              </span>
            </div>

            <div
                v-if="ordersBlockedByShift && !hasActiveShift"
                class="courier-card__empty state-message state-message--empty"
            >
              Откройте смену, чтобы видеть доступные заказы.
            </div>

            <div
                v-else-if="!availableOrders.length"
                class="courier-card__empty state-message state-message--empty"
            >
              Сейчас нет доступных заказов.
            </div>

            <ul
                v-else
                class="courier-orders"
            >
              <li
                  v-for="order in availableOrders"
                  :key="order.id"
                  class="courier-order surface-card--soft"
              >
                <div class="courier-order__main">
                  <div class="courier-order__top">
                    <span class="courier-order__number">
                      Заказ #{{ order.id }}
                    </span>
                    <span class="courier-order__price">
                      {{ formatPrice(order.total_price) }}
                    </span>
                  </div>

                  <div class="courier-order__restaurant">
                    {{ order.restaurant?.name || 'Ресторан' }}
                  </div>

                  <!-- адрес ресторана, если придёт с бэка -->
                  <div
                      v-if="getRestaurantAddress(order)"
                      class="courier-order__address"
                  >
                    Откуда:
                    <span>
                      {{ getRestaurantAddress(order) }}
                    </span>
                  </div>

                  <div
                      v-if="getDeliveryAddress(order)"
                      class="courier-order__address"
                  >
                    Куда:
                    <span>
                      {{ getDeliveryAddress(order) }}
                    </span>
                  </div>

                  <div class="courier-order__meta">
                    <span>
                      {{ order.items_count ?? '—' }} позиций
                    </span>
                    <span>
                      {{ formatDateTime(order.created_at) }}
                    </span>
                  </div>
                </div>

                <div class="courier-order__actions">
                  <button
                      type="button"
                      class="button courier-order__btn"
                      :disabled="
                      !hasActiveShift ||
                      (!!actionOrderId && actionOrderId === order.id)
                    "
                      @click="doAssign(order.id)"
                  >
                    <template
                        v-if="actionOrderId === order.id && actionType === 'assign'"
                    >
                      Берём заказ...
                    </template>
                    <template v-else>
                      Взять заказ
                    </template>
                  </button>
                </div>
              </li>
            </ul>
          </section>
        </div>

        <!-- Правая колонка -->
        <div class="courier__right">
          <!-- Активные заказы -->
          <section class="courier-card surface-card">
            <div class="courier-card__header section-head">
              <h2 class="courier-card__title section-title">
                Активные заказы
              </h2>
              <span class="courier-card__meta section-meta">
                {{ activeOrders.length }} шт.
              </span>
            </div>

            <div
                v-if="ordersBlockedByShift && !hasActiveShift"
                class="courier-card__empty state-message state-message--empty"
            >
              Откройте смену, чтобы брать заказы и отмечать доставку.
            </div>

            <div
                v-else-if="!activeOrders.length"
                class="courier-card__empty state-message state-message--empty"
            >
              У вас пока нет активных заказов.
            </div>

            <ul
                v-else
                class="courier-orders"
            >
              <li
                  v-for="order in activeOrders"
                  :key="order.id"
                  class="courier-order courier-order--active surface-card--soft"
              >
                <div class="courier-order__main">
                  <div class="courier-order__top">
                    <span class="courier-order__number">
                      Заказ #{{ order.id }}
                    </span>
                    <span class="courier-order__price">
                      {{ formatPrice(order.total_price) }}
                    </span>
                  </div>

                  <div class="courier-order__restaurant">
                    {{ order.restaurant?.name || 'Ресторан' }}
                  </div>

                  <div
                      v-if="getRestaurantAddress(order)"
                      class="courier-order__address"
                  >
                    Откуда:
                    <span>
                      {{ getRestaurantAddress(order) }}
                    </span>
                  </div>

                  <div
                      v-if="getDeliveryAddress(order)"
                      class="courier-order__address"
                  >
                    Куда:
                    <span>
                      {{ getDeliveryAddress(order) }}
                    </span>
                  </div>

                  <div class="courier-order__meta">
                    <span>
                      {{ order.items_count ?? '—' }} позиций
                    </span>
                    <span>
                      {{ formatDateTime(order.created_at) }}
                    </span>
                  </div>

                  <div
                      class="courier-order__status status-chip status-chip--muted"
                      :data-status="order.status"
                  >
                    <span class="courier-order__status-dot"/>
                      Статус:
                      <span class="courier-order__status-text">
                        {{ getCourierOrderStatusLabel(order.status) }}
                      </span>
                  </div>
                </div>

                <div class="courier-order__actions">
                  <button
                      v-if="canCourierMarkPickedUp(order)"
                      type="button"
                      class="button courier-order__btn"
                      :disabled="
                      !hasActiveShift ||
                      (!!actionOrderId && actionOrderId === order.id)
                    "
                      @click="doPickup(order.id)"
                  >
                    <template
                        v-if="actionOrderId === order.id && actionType === 'pickup'"
                    >
                      Подтверждаем забор...
                    </template>
                    <template v-else>
                      Заказ забран
                    </template>
                  </button>

                  <button
                      v-else-if="canCourierMarkDelivered(order)"
                      type="button"
                      class="button courier-order__btn"
                      :disabled="
                      !hasActiveShift ||
                      (!!actionOrderId && actionOrderId === order.id)
                    "
                      @click="doDeliver(order.id)"
                  >
                    <template
                        v-if="actionOrderId === order.id && actionType === 'deliver'"
                    >
                      Подтверждаем доставку...
                    </template>
                    <template v-else>
                      Доставлен
                    </template>
                  </button>

                  <span
                      v-else
                      class="courier-order__hint"
                  >
                    Ожидает следующего шага
                  </span>
                </div>
              </li>
            </ul>
          </section>

          <!-- История -->
          <section class="courier-card surface-card">
            <div class="courier-card__header section-head">
              <h2 class="courier-card__title section-title">
                История заказов
              </h2>
              <span class="courier-card__meta section-meta">
                {{ historyOrders.length }} шт.
              </span>
            </div>

            <div
                v-if="!historyOrders.length"
                class="courier-card__empty state-message state-message--empty"
            >
              История пока пуста.
            </div>

            <ul
                v-else
                class="courier-history"
            >
              <li
                  v-for="order in historyOrders"
                  :key="order.id"
                  class="courier-history__item"
              >
                <div class="courier-history__main">
                  <span class="courier-history__number">
                    #{{ order.id }}
                  </span>
                  <span class="courier-history__restaurant">
                    {{ order.restaurant?.name || 'Ресторан' }}
                  </span>
                </div>
                <div class="courier-history__meta">
                  <span class="courier-history__price">
                    {{ formatPrice(order.total_price) }}
                  </span>
                  <span class="courier-history__time">
                    {{ formatDateTime(order.created_at) }}
                  </span>
                </div>
              </li>
            </ul>
          </section>
        </div>
      </div>
    </div>
  </section>
</template>

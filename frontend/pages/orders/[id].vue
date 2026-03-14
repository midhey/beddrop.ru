<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import { useOrderDetailsPage } from '~/composables/useOrderDetailsPage';

const {
  current,
  currentLoading,
  errorMessage,
  id,
  sortedEvents,
  formatPrice,
  formatDateTime,
  getOrderStatusClass,
  getOrderStatusLabel,
  getPaymentMethodLabel,
  getPaymentStatusLabel,
} = useOrderDetailsPage();
</script>

<template>
  <section class="order-page page-shell">
    <div class="order-page__container container">
      <button
          type="button"
          class="order-page__back page-back"
          @click="$router.back()"
      >
        <ArrowLeft class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
        <span>Назад</span>
      </button>

      <div class="order-page__header page-head">
        <h1 class="order-page__title page-title">
          Заказ #{{ current?.id ?? id }}
        </h1>

        <span
            v-if="current"
            class="order-status status-chip"
            :class="getOrderStatusClass(current.status)"
        >
          {{ getOrderStatusLabel(current.status) }}
        </span>
      </div>

      <div
          v-if="currentLoading"
          class="order-page__loading state-message state-message--loading"
      >
        Загрузка заказа...
      </div>

      <div
          v-else-if="errorMessage"
          class="order-page__error state-message state-message--error"
      >
        {{ errorMessage }}
      </div>

      <div
          v-else-if="!current"
          class="order-page__empty state-message state-message--empty"
      >
        Заказ не найден.
      </div>

      <div
        v-else
        class="order-page__content"
      >
        <!-- Блок ресторана и общая информация -->
        <div class="order-page__card surface-card">
          <div class="order-page__row">
            <div class="order-page__col">
              <div class="order-page__label">
                Ресторан
              </div>
              <div class="order-page__value">
                {{ current.restaurant?.name || 'Неизвестно' }}
              </div>
            </div>
            <div class="order-page__col">
              <div class="order-page__label">
                Создан
              </div>
              <div class="order-page__value">
                {{ formatDateTime(current.created_at) }}
              </div>
            </div>
          </div>

          <div class="order-page__row">
            <div class="order-page__col">
              <div class="order-page__label">
                Оплата
              </div>
              <div class="order-page__value">
                {{ getPaymentMethodLabel(current.payment_method) }}
                ·
                {{ getPaymentStatusLabel(current.payment_status) }}
              </div>
            </div>
            <div class="order-page__col">
              <div class="order-page__label">
                Сумма заказа
              </div>
              <div class="order-page__value order-page__value--price">
                {{ formatPrice(current.total_price) }}
              </div>
            </div>
          </div>

          <div
              v-if="current.comment"
              class="order-page__row"
          >
            <div class="order-page__col order-page__col--full">
              <div class="order-page__label">
                Комментарий к заказу
              </div>
              <div class="order-page__value">
                {{ current.comment }}
              </div>
            </div>
          </div>
        </div>

        <!-- Состав заказа -->
        <div class="order-page__card surface-card">
          <div class="order-page__section-header section-head">
            <h2 class="section-title">Состав заказа</h2>
            <span class="order-page__section-meta section-meta">
              {{ current.items_count ?? '—' }} позиций
            </span>
          </div>

          <ul class="order-page__items">
            <li
                v-for="item in current.items"
                :key="item.id"
                class="order-item"
            >
              <div class="order-item__image-wrapper">
                <img
                    v-if="item.product?.images && item.product.images.length"
                    :src="item.product.images[0].media.url"
                    :alt="item.name_snapshot"
                    class="order-item__image"
                    loading="lazy"
                >
                <div
                    v-else
                    class="order-item__image order-item__image--placeholder"
                >
                  Без изображения
                </div>
              </div>

              <div class="order-item__body">
                <div class="order-item__top">
                  <div class="order-item__name">
                    {{ item.name_snapshot }}
                  </div>
                  <div class="order-item__price">
                    {{ formatPrice(item.unit_price_snapshot) }}
                  </div>
                </div>

                <div class="order-item__bottom">
                  <span class="order-item__qty">
                    {{ item.quantity }} шт.
                  </span>
                  <span class="order-item__subtotal">
                    {{ formatPrice(item.subtotal) }}
                  </span>
                </div>
              </div>
            </li>
          </ul>
        </div>

        <!-- Таймлайн событий -->
        <div
            v-if="sortedEvents.length"
            class="order-page__card surface-card"
        >
          <div class="order-page__section-header section-head">
            <h2 class="section-title">Статус заказа</h2>
          </div>

          <ul class="order-page__timeline">
            <li
                v-for="event in sortedEvents"
                :key="event.id"
                class="timeline-item"
            >
              <div class="timeline-item__dot"></div>
              <div class="timeline-item__content">
                <div class="timeline-item__title">
                  {{ getOrderStatusLabel(event.event) }}
                </div>
                <div class="timeline-item__time">
                  {{ formatDateTime(event.created_at) }}
                </div>
              </div>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ArrowLeft, CreditCard, Clock, MapPin, ClipboardList, Info } from "lucide-vue-next";
import RouteMap from "~/components/map/RouteMap.vue";
import { useOrderDetailsPage } from "~/composables/useOrderDetailsPage";

const config = useRuntimeConfig();

const {
  current,
  currentLoading,
  errorMessage,
  id,
  sortedEvents,
  routeDistanceKm,
  deliveryDurationMinutes,
  logisticsTimeBreakdown,
  isDelayed,
  isFinal,
  formatPrice,
  formatDateTime,
  getOrderStatusClass,
  getOrderStatusLabel,
  getPaymentMethodLabel,
  getPaymentStatusLabel,
  getDeliveryProgress,
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
        <ArrowLeft
          class="ui-icon"
          :size="16"
          :stroke-width="1.9"
          aria-hidden="true"
        />
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

      <div v-else class="order-page__content">
        <!-- Блок ресторана и общая информация -->
        <div class="order-page__card surface-card">
          <div class="order-page__row">
            <div class="order-page__col">
              <div class="order-page__label">
                <MapPin :size="12" class="ui-icon" />
                <span>Ресторан</span>
              </div>
              <div class="order-page__value">
                {{ current.restaurant?.name || "Неизвестно" }}
              </div>
            </div>
            <div class="order-page__col">
              <div class="order-page__label">
                <Clock :size="12" class="ui-icon" />
                <span>Создан</span>
              </div>
              <div class="order-page__value">
                {{ formatDateTime(current.created_at) }}
              </div>
            </div>
          </div>

          <div class="order-page__row">
            <div class="order-page__col">
              <div class="order-page__label">
                <CreditCard :size="12" class="ui-icon" />
                <span>Оплата</span>
              </div>
              <div class="order-page__value">
                {{ getPaymentMethodLabel(current.payment_method) }}
                ·
                <span :class="current.payment_status === 'PAID' ? 'order-status--success' : 'order-status--info'" class="status-chip">
                  {{ getPaymentStatusLabel(current.payment_status) }}
                </span>
              </div>
            </div>
            <div class="order-page__col">
              <div class="order-page__label">
                <ClipboardList :size="12" class="ui-icon" />
                <span>Сумма заказа</span>
              </div>
              <div class="order-page__value order-page__value--price">
                {{ formatPrice(current.total_price) }}
              </div>
            </div>
          </div>

          <div v-if="current.comment" class="order-page__row">
            <div class="order-page__col order-page__col--full">
              <div class="order-page__label">
                <Info :size="12" class="ui-icon" />
                <span>Комментарий к заказу</span>
              </div>
              <div class="order-page__value">
                {{ current.comment }}
              </div>
            </div>
          </div>
        </div>

        <!-- Плейсхолдер для оплаты (вызывающий блок) -->
        <div
          v-if="current.payment_status === 'PENDING'"
          class="order-page__payment-alert"
        >
          <div class="order-page__payment-alert-head">
            <div class="order-page__payment-alert-icon">
              <CreditCard :size="20" class="ui-icon" />
            </div>
            <h2 class="order-page__payment-alert-title">Ожидаем оплату</h2>
          </div>
          <p class="order-page__payment-alert-text">
            Заказ поступит в ресторан сразу после подтверждения платежа:
          </p>

          <img
            :src="
              'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' +
              encodeURIComponent(
                config.public.apiBase.replace('/api/v1', '') +
                  '/api/v1/orders/' +
                  current.id +
                  '/mock-pay',
              )
            "
            alt="Оплатить по QR"
            class="order-page__payment-qr"
          />
        </div>

        <div
          v-if="current.route_segments?.length && current.payment_status !== 'PENDING'"
          class="order-page__card surface-card"
        >
          <div class="order-page__section-header section-head">
            <h2 class="section-title">Ожидаемое время и маршрут доставки</h2>
            <span
              v-if="routeDistanceKm"
              class="order-page__section-meta section-meta"
            >
              {{ routeDistanceKm }} км
            </span>
          </div>

          <div
            v-if="!isFinal"
            class="order-page__delivery-status"
            :class="{ 'order-page__delivery-status--delayed': current.payment_status === 'PAID' && isDelayed }"
          >
            <div class="order-page__delivery-main">
              <div class="order-page__delivery-info">
                <span class="order-page__delivery-label">
                  {{ isDelayed ? 'Опаздываем' : 'Ожидаемое время доставки' }}
                </span>
                <strong v-if="current.estimated_delivery_at" class="order-page__delivery-time">
                  {{ formatDateTime(current.estimated_delivery_at).split(',')[1].trim() }}
                </strong>
                <strong v-else class="order-page__delivery-time">Считаем...</strong>
              </div>
              <div v-if="current.logistics_snapshot?.time?.total" class="order-page__delivery-badge">
                <Clock :size="14" />
                <span>~{{ current.logistics_snapshot.time.total }} мин</span>
              </div>
            </div>
            
            <p v-if="current.payment_status === 'PAID' && isDelayed" class="order-page__delivery-apology">
              Извините, мы немного задерживаемся. Курьер уже спешит к вам!
            </p>

            <div v-if="current.payment_status === 'PAID'" class="order-page__delivery-progress">
              <div 
                class="order-page__delivery-progress-bar" 
                :style="{ width: getDeliveryProgress(current.status, current.payment_status) + '%' }"
              ></div>
            </div>
          </div>

          <RouteMap
            :route-segments="current.route_segments"
            :restaurant-address="current.restaurant?.address"
            :delivery-address="current.delivery_address"
            :height="320"
          />
        </div>

        <!-- Состав заказа -->
        <div class="order-page__card surface-card">
          <div class="order-page__section-header section-head">
            <h2 class="section-title">Состав заказа</h2>
            <span class="order-page__section-meta section-meta">
              {{ current.items_count ?? "—" }} позиций
            </span>
          </div>

          <ul class="order-page__items">
            <li v-for="item in current.items" :key="item.id" class="order-item">
              <div class="order-item__image-wrapper">
                <img
                  v-if="item.product?.images && item.product.images.length"
                  :src="item.product.images[0].media.url"
                  :alt="item.name_snapshot"
                  class="order-item__image"
                  loading="lazy"
                />
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
                  <span class="order-item__qty"> {{ item.quantity }} шт. </span>
                  <span class="order-item__subtotal">
                    {{ formatPrice(item.subtotal) }}
                  </span>
                </div>
              </div>
            </li>
          </ul>
        </div>

        <!-- Таймлайн событий -->
        <div v-if="sortedEvents.length" class="order-page__card surface-card">
          <div class="order-page__section-header section-head">
            <h2 class="section-title">Статус заказа</h2>
          </div>

          <ul class="order-page__timeline">
            <li
              v-for="(event, index) in sortedEvents"
              :key="event.id"
              class="timeline-item"
              :class="{ 'timeline-item--completed': index < sortedEvents.length - 1 }"
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

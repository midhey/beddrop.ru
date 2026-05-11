<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import RouteMap from '~/components/map/RouteMap.vue';
import { useCheckoutPage } from '~/composables/useCheckoutPage';

const {
  addresses,
  addressesError,
  orderError,
  quoteError,
  cart,
  cartError,
  pageLoading,
  isCartEmpty,
  selectedAddressId,
  paymentMethod,
  comment,
  cartTotal,
  deliveryQuote,
  quoteLoading,
  deliveryRoutePrice,
  serviceFee,
  checkoutTotal,
  cartItemsCount,
  restaurantName,
  restaurantClosedText,
  restaurantAddress,
  selectedAddress,
  deliveryDistanceKm,
  deliveryDurationMinutes,
  deliveryTimeBreakdown,
  quoteRouteSegments,
  canSubmit,
  submitOrder,
  formatPrice,
} = useCheckoutPage();
</script>

<template>
  <section class="checkout-page page-shell">
    <div class="checkout-page__container container">
      <button
          type="button"
          class="checkout-page__back page-back"
          @click="$router.back()"
      >
        <ArrowLeft class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
        <span>Назад</span>
      </button>

      <div class="page-head">
        <div>
          <h1 class="checkout-page__title page-title">
            Оформление заказа
          </h1>

          <p class="checkout-page__subtitle page-subtitle">
            Проверьте корзину, выберите адрес доставки и способ оплаты.
          </p>
        </div>
      </div>

      <div
          v-if="cartError || addressesError || orderError || quoteError"
          class="checkout-page__error-list"
      >
        <p
            v-if="cartError"
            class="checkout-page__error state-message state-message--error"
        >
          {{ cartError }}
        </p>
        <p
            v-if="addressesError"
            class="checkout-page__error state-message state-message--error"
        >
          {{ addressesError }}
        </p>
        <p
            v-if="orderError"
            class="checkout-page__error state-message state-message--error"
        >
          {{ orderError }}
        </p>
        <p
            v-if="quoteError"
            class="checkout-page__error state-message state-message--error"
        >
          {{ quoteError }}
        </p>
      </div>

      <div
          v-if="pageLoading"
          class="checkout-page__loading state-message state-message--loading"
      >
        Загружаем данные...
      </div>

      <div
          v-else-if="isCartEmpty"
          class="checkout-page__empty state-message state-message--empty"
      >
        <p>Ваша корзина пуста, оформить заказ нельзя.</p>
        <NuxtLink to="/" class="button">
          Найти ресторан
        </NuxtLink>
      </div>

      <div
          v-else
          class="checkout-page__layout"
      >
        <!-- левая колонка -->
        <div class="checkout-page__main">
          <!-- Адреса -->
          <section class="checkout-card surface-card">
            <h2 class="checkout-card__title section-title">
              Адрес доставки
            </h2>

            <p
                v-if="!addresses.length"
                class="checkout-card__hint"
            >
              У вас нет сохранённых адресов. Перейдите в профиль и добавьте хотя бы один адрес.
            </p>

            <div
                v-else
                class="checkout-addresses"
            >
              <label
                  v-for="addr in addresses"
                  :key="addr.id"
                  class="checkout-address"
              >
                <input
                    v-model="selectedAddressId"
                    type="radio"
                    :value="addr.id"
                    class="checkout-address__radio"
                >
                <div class="checkout-address__body">
                  <div class="checkout-address__top-line">
                    <span
                        v-if="addr.label"
                        class="checkout-address__label"
                    >
                      {{ addr.label }}
                    </span>
                    <span class="checkout-address__city">
                      {{ addr.city || addr.settlement || 'Населённый пункт не указан' }}
                    </span>
                  </div>
                  <div class="checkout-address__line1">
                    {{ addr.value || addr.line1 }}
                    <span
                        v-if="addr.flat || addr.entrance || addr.floor"
                        class="checkout-address__line2"
                    >
                      , {{ [
                        addr.entrance ? `подъезд ${addr.entrance}` : null,
                        addr.floor ? `этаж ${addr.floor}` : null,
                        addr.flat ? `кв. ${addr.flat}` : null,
                      ].filter(Boolean).join(', ') }}
                    </span>
                  </div>
                  <div
                      v-if="addr.postal_code"
                      class="checkout-address__postal"
                  >
                    {{ addr.postal_code }}
                  </div>
                </div>
              </label>

              <NuxtLink
                  to="/profile/addresses"
                  class="checkout-card__link"
              >
                Управлять адресами
              </NuxtLink>
            </div>
          </section>

          <section class="checkout-card surface-card">
            <h2 class="checkout-card__title section-title">
              Маршрут доставки
            </h2>

            <div
                v-if="quoteLoading"
                class="checkout-route checkout-route--loading"
            >
              Считаем маршрут...
            </div>

            <div
                v-else-if="deliveryQuote && quoteRouteSegments.length"
                class="checkout-route"
            >
              <div class="checkout-route__stats">
                <div class="checkout-route__stat">
                  <span class="checkout-route__label">Расстояние</span>
                  <strong>{{ deliveryDistanceKm }} км</strong>
                </div>
                <div class="checkout-route__stat">
                  <span class="checkout-route__label">В пути</span>
                  <strong>{{ deliveryDurationMinutes }} мин</strong>
                </div>
                <div class="checkout-route__stat">
                  <span class="checkout-route__label">Итого до двери</span>
                  <strong>{{ deliveryQuote.eta_minutes }} мин</strong>
                </div>
              </div>

              <div class="checkout-route__breakdown">
                <div
                    v-for="item in deliveryTimeBreakdown"
                    :key="item.label"
                    class="checkout-route__breakdown-item"
                >
                  <span>{{ item.label }}</span>
                  <strong>{{ item.value }} мин</strong>
                </div>
              </div>

              <RouteMap
                  :route-segments="quoteRouteSegments"
                  :restaurant-address="restaurantAddress"
                  :delivery-address="selectedAddress"
                  :height="320"
              />
            </div>

            <p
                v-else
                class="checkout-card__hint checkout-card__hint--warning"
            >
              Выберите адрес с координатами, чтобы увидеть маршрут и стоимость доставки.
            </p>
          </section>

          <!-- Оплата + комментарий -->
          <section class="checkout-card surface-card">
            <h2 class="checkout-card__title section-title">
              Способ оплаты
            </h2>

            <div class="checkout-payment">
              <label class="checkout-payment__option">
                <input
                    v-model="paymentMethod"
                    type="radio"
                    value="CASH"
                >
                <span>Наличными курьеру</span>
              </label>

              <label class="checkout-payment__option">
                <input
                    v-model="paymentMethod"
                    type="radio"
                    value="CARD"
                >
                <span>Картой курьеру</span>
              </label>

              <label class="checkout-payment__option">
                <input
                    v-model="paymentMethod"
                    type="radio"
                    value="ONLINE"
                >
                <span>Онлайн-оплата (не работает)</span>
              </label>
            </div>

            <div class="checkout-comment form-field">
              <label class="checkout-comment__label">
                Комментарий к заказу
              </label>
              <textarea
                  v-model="comment"
                  rows="3"
                  class="checkout-comment__textarea field-textarea"
                  placeholder="Например: позвоните за 5 минут до приезда..."
              ></textarea>
            </div>
          </section>
        </div>

        <!-- правая колонка: корзина -->
        <aside class="checkout-page__sidebar">
          <section class="checkout-card checkout-card--sticky surface-card">
            <h2 class="checkout-card__title section-title">
              Заказ в "{{ restaurantName }}"
            </h2>

            <p
                v-if="restaurantClosedText"
                class="checkout-card__hint checkout-card__hint--warning checkout-card__hint--strong"
            >
              {{ restaurantClosedText }}
            </p>

            <ul class="checkout-cart">
              <li
                  v-for="item in cart?.items || []"
                  :key="item.id"
                  class="checkout-cart__item"
              >
                <div class="checkout-cart__info">
                  <div class="checkout-cart__name">
                    {{ item.product?.name || item.product_id }}
                  </div>
                  <div class="checkout-cart__meta">
                    <span class="checkout-cart__qty">
                      {{ item.quantity }} шт.
                    </span>
                    <span class="checkout-cart__unit-price">
                      {{ formatPrice(item.unit_price_snapshot) }} / шт.
                    </span>
                  </div>
                </div>
                <div class="checkout-cart__subtotal">
                  {{ formatPrice(item.subtotal) }}
                </div>
              </li>
            </ul>

            <div class="checkout-summary">
              <div class="checkout-summary__row">
                <span>Товары ({{ cartItemsCount }} поз.)</span>
                <span>{{ formatPrice(cartTotal) }}</span>
              </div>
              <div class="checkout-summary__row">
                <span>Доставка</span>
                <span v-if="quoteLoading">Считаем...</span>
                <span v-else>{{ formatPrice(deliveryRoutePrice) }}</span>
              </div>
              <div class="checkout-summary__row">
                <span>Сервисный сбор</span>
                <span v-if="quoteLoading">...</span>
                <span v-else>{{ formatPrice(serviceFee) }}</span>
              </div>
              <div
                  v-if="deliveryQuote"
                  class="checkout-summary__route"
              >
                {{ deliveryDistanceKm }} км, в пути ~{{ deliveryDurationMinutes }} мин, готовка ~{{ deliveryQuote.time.prep }} мин, итого ~{{ deliveryQuote.eta_minutes }} мин
              </div>
              <div class="checkout-summary__row checkout-summary__row--total">
                <span>Итого к оплате</span>
                <span>{{ formatPrice(checkoutTotal) }}</span>
              </div>
            </div>

            <button
                type="button"
                class="button button--primary checkout-card__submit"
                :disabled="!canSubmit"
                @click="submitOrder"
            >
              Оформить заказ
            </button>

            <p
                v-if="selectedAddressId && !deliveryQuote && !quoteLoading"
                class="checkout-card__hint checkout-card__hint--warning"
            >
              Не удалось рассчитать доставку. Проверьте адрес или попробуйте позже.
            </p>

            <p
                v-if="restaurantClosedText"
                class="checkout-card__hint checkout-card__hint--warning"
            >
              Новые заказы сейчас недоступны.
            </p>

            <p
                v-if="!selectedAddressId && addresses.length"
                class="checkout-card__hint checkout-card__hint--warning"
            >
              Выберите адрес доставки, чтобы оформить заказ.
            </p>

            <p
                v-else-if="!addresses.length"
                class="checkout-card__hint checkout-card__hint--warning"
            >
              Добавьте хотя бы один адрес в профиле.
            </p>
          </section>
        </aside>
      </div>
    </div>
  </section>
</template>

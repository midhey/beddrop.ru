<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import { useCheckoutPage } from '~/composables/useCheckoutPage';

const {
  addresses,
  addressesError,
  orderError,
  cart,
  cartError,
  pageLoading,
  isCartEmpty,
  selectedAddressId,
  paymentMethod,
  comment,
  cartTotal,
  cartItemsCount,
  restaurantName,
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
          v-if="cartError || addressesError || orderError"
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
                      {{ addr.city || 'Город не указан' }}
                    </span>
                  </div>
                  <div class="checkout-address__line1">
                    {{ addr.line1 }}
                    <span
                        v-if="addr.line2"
                        class="checkout-address__line2"
                    >
                      , {{ addr.line2 }}
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
              <div class="checkout-summary__row checkout-summary__row--total">
                <span>Итого к оплате</span>
                <span>{{ formatPrice(cartTotal) }}</span>
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

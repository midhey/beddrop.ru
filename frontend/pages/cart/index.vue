<script setup lang="ts">
import { computed, onMounted } from "vue";
import {
  ArrowLeft,
  Minus,
  Plus,
  ShieldCheck,
  ShoppingBasket,
  Store,
  Trash2,
} from "lucide-vue-next";
import { useRouter, useNuxtApp } from "#app";
import { useCartStore } from "~/stores/cart";
import type { CartItem } from "~/stores/cart";

import placeholderImg from "~/assets/images/placeholder.png";

const cartStore = useCartStore();
const router = useRouter();
const { $notify } = useNuxtApp();

const loading = computed(() => cartStore.loading);
const items = computed(() => cartStore.items);
const totalPrice = computed(() => cartStore.totalPrice);
const totalCount = computed(() => cartStore.totalCount);
const restaurant = computed(() => cartStore.restaurant);
const positionsCount = computed(() => items.value.length);

const formatPrice = (value: number | string) =>
  new Intl.NumberFormat("ru-RU", {
    style: "currency",
    currency: "RUB",
    maximumFractionDigits: 0,
  }).format(Number(value) || 0);

const pluralize = (count: number, one: string, few: string, many: string) => {
  const mod10 = count % 10;
  const mod100 = count % 100;

  if (mod10 === 1 && mod100 !== 11) {
    return one;
  }

  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) {
    return few;
  }

  return many;
};

const cartSubtitle = computed(() => {
  if (!items.value.length) {
    return "Соберите заказ из любимого ресторана и оформите доставку в пару кликов.";
  }

  return `${totalCount.value} ${pluralize(totalCount.value, "товар", "товара", "товаров")} в ${positionsCount.value} ${pluralize(positionsCount.value, "позиции", "позициях", "позициях")}`;
});

const getProductImage = (item: CartItem) => {
  const coverImage = item.product.images?.find((image) => image.is_cover)?.media
    ?.url;

  if (coverImage) {
    return coverImage;
  }

  if (item.product.images?.[0]?.media?.url) {
    return item.product.images[0].media.url;
  }

  return placeholderImg;
};

// грузим корзину при заходе на страницу (на случай прямого входа)
onMounted(async () => {
  try {
    await cartStore.fetchCart();
  } catch {
    // ошибки уже показаны через $notify внутри стора
  }
});

const goToCheckout = async () => {
  if (!items.value.length) {
    $notify?.failure?.("Сначала добавьте товары в корзину");
    return;
  }

  await router.push("/orders/create");
};

const goToRestaurantMenu = async () => {
  if (restaurant.value?.slug) {
    await router.push(`/restaurants/${restaurant.value.slug}`);
    return;
  }

  await router.push("/");
};

const clearCart = async () => {
  try {
    await cartStore.clearCart();
  } catch {}
};

const removeItem = async (productId: number) => {
  try {
    await cartStore.removeProduct(productId);
  } catch {}
};

const changeQuantity = async (item: CartItem, nextQuantity: number) => {
  if (nextQuantity < 0) {
    return;
  }

  try {
    await cartStore.setProductQuantity(item.product, nextQuantity);
  } catch {}
};
</script>

<template>
  <section class="cart page-shell">
    <div class="cart__container container">
      <button
        type="button"
        class="cart__back page-back"
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

      <div class="cart__head page-head">
        <div>
          <h1 class="cart__title page-title">Корзина</h1>

          <p class="cart__subtitle page-subtitle">
            {{ cartSubtitle }}
          </p>
        </div>

        <button
          v-if="items.length"
          type="button"
          class="button button--ghost button--small cart__head-action"
          @click="clearCart"
        >
          <Trash2
            class="ui-icon"
            :size="16"
            :stroke-width="1.9"
            aria-hidden="true"
          />
          Очистить корзину
        </button>
      </div>

      <div
        v-if="loading"
        class="cart__loading state-message state-message--loading surface-card"
      >
        Загружаем корзину и проверяем актуальный состав заказа...
      </div>

      <div v-else-if="!items.length" class="cart__empty surface-card">
        <div class="cart__empty-icon">
          <ShoppingBasket
            class="ui-icon"
            :size="28"
            :stroke-width="1.9"
            aria-hidden="true"
          />
        </div>

        <div class="cart__empty-copy">
          <h2 class="cart__empty-title">В корзине пока пусто</h2>
          <p class="cart__empty-text">
            Добавьте блюда из меню ресторана, и здесь появится сводка заказа в
            стиле маркетплейса.
          </p>
        </div>

        <div class="cart__empty-actions">
          <NuxtLink to="/" class="button"> Выбрать ресторан </NuxtLink>
        </div>
      </div>

      <div v-else class="cart__content">
        <div class="cart__main">
          <section v-if="restaurant" class="cart__restaurant-card surface-card">
            <div class="cart__restaurant-copy">
              <span
                class="cart__restaurant-badge status-chip status-chip--info"
              >
                <Store
                  class="ui-icon"
                  :size="14"
                  :stroke-width="1.9"
                  aria-hidden="true"
                />
                Один ресторан
              </span>

              <h2 class="cart__restaurant-title">
                {{ restaurant.name }}
              </h2>

              <p class="cart__restaurant-note">
                Все позиции в корзине будут оформлены одним заказом, как в
                карточке продавца на маркетплейсе.
              </p>
            </div>

            <button
              type="button"
              class="button button--ghost button--small cart__restaurant-action"
              @click="goToRestaurantMenu"
            >
              Добавить ещё блюда
            </button>
          </section>

          <ul class="cart__list">
            <li
              v-for="item in items"
              :key="item.id"
              class="cart__item surface-card"
            >
              <div class="cart__item-media">
                <img
                  :src="getProductImage(item)"
                  :alt="item.product.name"
                  class="cart__item-image"
                  loading="lazy"
                />
              </div>

              <div class="cart__item-body">
                <div class="cart__item-top">
                  <div class="cart__item-copy">
                    <h2 class="cart__item-title">
                      {{ item.product.name }}
                    </h2>

                    <p
                      v-if="item.product.description"
                      class="cart__item-description"
                    >
                      {{ item.product.description }}
                    </p>

                    <div class="cart__item-meta">
                      <span class="cart__item-price">
                        {{ formatPrice(item.unit_price_snapshot) }} / шт.
                      </span>
                    </div>
                  </div>

                  <button
                    type="button"
                    class="cart__item-remove"
                    aria-label="Удалить товар из корзины"
                    @click="removeItem(item.product_id)"
                  >
                    <Trash2
                      class="ui-icon"
                      :size="18"
                      :stroke-width="1.9"
                      aria-hidden="true"
                    />
                  </button>
                </div>

                <div class="cart__item-bottom">
                  <div class="cart__item-controls">
                    <button
                      type="button"
                      class="cart__qty-btn"
                      :disabled="item.quantity === 0"
                      aria-label="Уменьшить количество"
                      @click="changeQuantity(item, item.quantity - 1)"
                    >
                      <Minus
                        class="ui-icon"
                        :size="16"
                        :stroke-width="1.9"
                        aria-hidden="true"
                      />
                    </button>
                    <span class="cart__qty-value">
                      {{ item.quantity }}
                    </span>
                    <button
                      type="button"
                      class="cart__qty-btn"
                      aria-label="Увеличить количество"
                      @click="changeQuantity(item, item.quantity + 1)"
                    >
                      <Plus
                        class="ui-icon"
                        :size="16"
                        :stroke-width="1.9"
                        aria-hidden="true"
                      />
                    </button>
                  </div>

                  <div class="cart__item-total">
                    <span class="cart__item-total-label"> Итого </span>
                    <strong class="cart__item-total-value">
                      {{ formatPrice(item.subtotal) }}
                    </strong>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>

        <aside class="cart__sidebar">
          <section class="cart__summary surface-card">
            <div class="cart__summary-top">
              <h2 class="cart__summary-title">Ваш заказ</h2>
              <p class="cart__summary-subtitle">
                {{ restaurant?.name || "Корзина" }}
              </p>
            </div>

            <div class="cart__summary-rows">
              <div class="cart__summary-row">
                <span>Товары, {{ totalCount }} шт.</span>
                <span>{{ formatPrice(totalPrice) }}</span>
              </div>
              <div class="cart__summary-row">
                <span>Позиции</span>
                <span>{{ positionsCount }}</span>
              </div>
              <div class="cart__summary-row cart__summary-row--total">
                <span>Итого к оплате</span>
                <span>{{ formatPrice(totalPrice) }}</span>
              </div>
            </div>

            <button
              type="button"
              class="button cart__checkout-btn"
              :disabled="!items.length"
              @click="goToCheckout"
            >
              К оформлению
            </button>

            <div class="cart__summary-note">
              <ShieldCheck
                class="ui-icon"
                :size="16"
                :stroke-width="1.9"
                aria-hidden="true"
              />
              <span
                >Адрес, доставка и оплата подтверждаются на следующем
                шаге.</span
              >
            </div>
          </section>
        </aside>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { ArrowLeft, Clock3, Phone } from 'lucide-vue-next';
import { useRoute } from '#app';
import { useRestaurantPage } from '~/composables/useRestaurantPage';
import ProductCard from '~/components/product/ProductCard.vue';

const route = useRoute();
const slug = computed(() => route.params.slug as string);

const {
  restaurant,
  restaurantError,
  loading,
  productsError,
  filteredProducts,
  categories,
  selectedCategorySlug,
  fullAddress,
  prepTimeText,
  init,
} = useRestaurantPage(slug);

onMounted(init);
</script>

<template>
  <section class="restaurant-page">
    <div class="restaurant-page__container container">
      <button
          type="button"
          class="restaurant-page__back page-back"
          @click="$router.back()"
      >
        <ArrowLeft class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
        <span>Назад</span>
      </button>

      <div
          v-if="restaurantError"
          class="restaurant-page__error"
      >
        {{ restaurantError }}
      </div>

      <div
          v-else
          class="restaurant-page__header"
      >
        <div
            v-if="restaurant?.logo?.url"
            class="restaurant-page__logo"
        >
          <img
              :src="restaurant.logo.url"
              :alt="restaurant.name"
          >
        </div>

        <div class="restaurant-page__info">
          <h1 class="restaurant-page__title">
            {{ restaurant?.name || 'Ресторан' }}
          </h1>

          <p class="restaurant-page__address">
            {{ fullAddress }}
          </p>

          <div class="restaurant-page__meta">
            <span
                v-if="prepTimeText"
                class="restaurant-page__meta-item"
            >
              <Clock3 class="ui-icon" :size="14" :stroke-width="1.9" aria-hidden="true" />
              {{ prepTimeText }}
            </span>
            <span
                v-if="restaurant?.phone"
                class="restaurant-page__meta-item"
            >
              <Phone class="ui-icon" :size="14" :stroke-width="1.9" aria-hidden="true" />
              {{ restaurant.phone }}
            </span>
            <span
                v-if="restaurant && restaurant.is_active"
                class="restaurant-page__meta-item restaurant-page__meta-item--success"
            >
              Открыт для заказов
            </span>
            <span
                v-else-if="restaurant"
                class="restaurant-page__meta-item restaurant-page__meta-item--muted"
            >
              Временно недоступен
            </span>
          </div>
        </div>
      </div>

      <div class="restaurant-page__menu">
        <div class="restaurant-page__menu-header">
          <h2 class="restaurant-page__menu-title">
            Меню
          </h2>
          <span class="restaurant-page__menu-count">
            {{ filteredProducts.length }} позиций
          </span>
        </div>

        <div
            v-if="categories.length"
            class="restaurant-page__tabs"
        >
          <button
              type="button"
              class="restaurant-page__tab"
              :class="{ 'restaurant-page__tab--active': selectedCategorySlug === 'all' }"
              @click="selectedCategorySlug = 'all'"
          >
            Все
          </button>

          <button
              v-for="cat in categories"
              :key="cat.id"
              type="button"
              class="restaurant-page__tab"
              :class="{ 'restaurant-page__tab--active': selectedCategorySlug === cat.slug }"
              @click="selectedCategorySlug = cat.slug"
          >
            {{ cat.name }}
          </button>
        </div>

        <div
            v-if="productsError"
            class="restaurant-page__error"
        >
          {{ productsError }}
        </div>

        <div
            v-else-if="loading"
            class="restaurant-page__loading"
        >
          Загрузка меню...
        </div>

        <div
            v-else-if="!filteredProducts.length"
            class="restaurant-page__empty"
        >
          Меню пока пустое
        </div>

        <div
            v-else
            class="restaurant-page__products"
        >
          <ProductCard
              v-for="product in filteredProducts"
              :key="product.id"
              :product="product"
          />
        </div>
      </div>
    </div>
  </section>
</template>

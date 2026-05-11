<script setup lang="ts">
import { computed, TransitionGroup } from "vue";
import { ArrowLeft, Clock3, Phone } from "lucide-vue-next";
import { useRoute } from "#app";
import { useRestaurantPage } from "~/composables/useRestaurantPage";
import ProductCard from "~/components/product/ProductCard.vue";

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
  workingHoursText,
  availabilityText,
  availabilityStatus,
} = await useRestaurantPage(slug);
</script>

<template>
  <section class="restaurant-page">
    <div class="restaurant-page__container container">
      <button
        type="button"
        class="restaurant-page__back page-back"
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

      <div v-if="restaurantError" class="restaurant-page__error">
        {{ restaurantError }}
      </div>

      <div
        v-else-if="loading && !restaurant"
        class="restaurant-page__header restaurant-page__header--skeleton"
        aria-hidden="true"
      >
        <span
          class="restaurant-page__logo restaurant-page__logo--skeleton skeleton"
        />

        <div class="restaurant-page__info restaurant-page__info--skeleton">
          <span class="restaurant-page__title-skeleton skeleton" />
          <span class="restaurant-page__address-skeleton skeleton" />
          <div class="restaurant-page__meta restaurant-page__meta--skeleton">
            <span class="restaurant-page__meta-skeleton skeleton" />
            <span
              class="restaurant-page__meta-skeleton restaurant-page__meta-skeleton--short skeleton"
            />
          </div>
        </div>
      </div>

      <div v-else class="restaurant-page__header">
        <div v-if="restaurant?.logo?.url" class="restaurant-page__logo">
          <img :src="restaurant.logo.url" :alt="restaurant.name" />
        </div>

        <div class="restaurant-page__info">
          <h1 class="restaurant-page__title">
            {{ restaurant?.name || "Ресторан" }}
          </h1>

          <p class="restaurant-page__address">
            {{ fullAddress }}
          </p>

          <p v-if="restaurant?.description" class="restaurant-page__description">
            {{ restaurant.description }}
          </p>

          <div class="restaurant-page__meta">
            <span v-if="prepTimeText" class="restaurant-page__meta-item">
              <Clock3
                class="ui-icon"
                :size="14"
                :stroke-width="1.9"
                aria-hidden="true"
              />
              {{ prepTimeText }}
            </span>
            <a
              v-if="restaurant?.phone"
              class="restaurant-page__meta-item"
              :href="`tel:${restaurant.phone}`"
            >
              <Phone
                class="ui-icon"
                :size="14"
                :stroke-width="1.9"
                aria-hidden="true"
              />
              {{ restaurant.phone }}
            </a>
            <span
              v-if="restaurant"
              class="restaurant-page__meta-item restaurant-page__meta-item--success"
              :class="{
                'restaurant-page__meta-item--muted':
                  availabilityStatus === 'inactive',
              }"
            >
              {{ availabilityText }}
            </span>
            <span v-if="workingHoursText" class="restaurant-page__meta-item">
              {{ workingHoursText }}
            </span>
          </div>
        </div>
      </div>

      <div class="restaurant-page__menu">
        <div class="restaurant-page__menu-header">
          <h2 class="restaurant-page__menu-title">Меню</h2>
          <span
            v-if="loading && !filteredProducts.length"
            class="restaurant-page__menu-count-skeleton skeleton"
            aria-hidden="true"
          />
          <span v-else class="restaurant-page__menu-count">
            {{ filteredProducts.length }} позиций
          </span>
        </div>

        <div
          v-if="loading && !categories.length"
          class="restaurant-page__tabs restaurant-page__tabs--skeleton"
          aria-hidden="true"
        >
          <span
            v-for="index in 5"
            :key="`category-skeleton-${index}`"
            class="restaurant-page__tab-skeleton skeleton"
            :class="{ 'restaurant-page__tab-skeleton--short': index === 1 }"
          />
        </div>

        <div v-else-if="categories.length" class="restaurant-page__tabs">
          <button
            type="button"
            class="restaurant-page__tab"
            :class="{
              'restaurant-page__tab--active': selectedCategorySlug === 'all',
            }"
            @click="selectedCategorySlug = 'all'"
          >
            Все
          </button>

          <button
            v-for="cat in categories"
            :key="cat.id"
            type="button"
            class="restaurant-page__tab"
            :class="{
              'restaurant-page__tab--active': selectedCategorySlug === cat.slug,
            }"
            @click="selectedCategorySlug = cat.slug"
          >
            {{ cat.name }}
          </button>
        </div>

        <div v-if="productsError && !loading" class="restaurant-page__error">
          {{ productsError }}
        </div>

        <div
          v-else-if="loading && !filteredProducts.length"
          class="restaurant-page__products restaurant-page__products--skeleton"
          aria-hidden="true"
        >
          <article
            v-for="index in 6"
            :key="`product-skeleton-${index}`"
            class="restaurant-page__product-skeleton"
          >
            <span class="restaurant-page__product-image-skeleton skeleton" />
            <div class="restaurant-page__product-body-skeleton">
              <span
                class="restaurant-page__product-line-skeleton restaurant-page__product-line-skeleton--title skeleton"
              />
              <span class="restaurant-page__product-line-skeleton skeleton" />
              <span
                class="restaurant-page__product-line-skeleton restaurant-page__product-line-skeleton--short skeleton"
              />
            </div>
          </article>
        </div>

        <div
          v-else-if="!filteredProducts.length"
          class="restaurant-page__empty"
        >
          Меню пока пустое
        </div>

        <TransitionGroup
          v-else
          tag="div"
          name="restaurant-products"
          class="restaurant-page__products"
        >
          <ProductCard
            v-for="product in filteredProducts"
            :key="product.id"
            :product="product"
          />
        </TransitionGroup>
      </div>
    </div>
  </section>
</template>

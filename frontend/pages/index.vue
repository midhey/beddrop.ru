<script setup lang="ts">
import { computed, ref } from 'vue';
import CardsLayout from '~/components/ui/CardsLayout.vue';
import BaseScrollableTabs from '~/components/ui/BaseScrollableTabs.vue';
import RestaurantCard from '~/components/restaurants/RestaurantCard.vue';
import {
  useProductCategories,
  type ProductCategory,
} from '~/composables/useProductCategories';
import { listRestaurants } from '~/domains/restaurants/api';

const { $notify } = useNuxtApp();
const { fetchCategories } = useProductCategories();
const selectedCategoryId = ref<number | null>(null);

useAppSeoMeta({
  title: 'Рестораны рядом — BedDrop',
  description: 'Выберите ресторан на BedDrop, посмотрите меню и оформите доставку еды с прозрачным статусом заказа.',
});

type CategoryFilterOption = {
  key: string;
  id: number | null;
  name: string;
};

const {
  data: categoriesResponse,
  pending: categoriesPending,
  error: categoriesError,
} = await useAsyncData(
  'restaurants-category-filters',
  () => fetchCategories(),
  {
    default: () => [] as ProductCategory[],
  },
);

const restaurantQueryParams = computed(() => {
  if (selectedCategoryId.value === null) {
    return {};
  }

  return {
    category_id: selectedCategoryId.value,
  };
});

const {
  data: restaurantsResponse,
  pending: restaurantsPending,
  error: restaurantsError,
  refresh: refreshRestaurants,
} = await useAsyncData(
  'restaurants-index',
  () => listRestaurants(restaurantQueryParams.value),
);

const categories = computed(() => categoriesResponse.value ?? []);
const categoryFilters = computed<CategoryFilterOption[]>(() => [
  {
    key: 'all',
    id: null,
    name: 'Все',
  },
  ...categories.value.map((category) => ({
    key: String(category.id),
    id: category.id,
    name: category.name,
  })),
]);
const restaurants = computed(() => restaurantsResponse.value?.items ?? []);
const hasRestaurants = computed(() => restaurants.value.length > 0);
const activeCategoryName = computed(() => {
  if (selectedCategoryId.value === null) {
    return 'Все';
  }

  return categories.value.find((category) => category.id === selectedCategoryId.value)?.name
    ?? 'Выбранная категория';
});
const restaurantsErrorMessage = computed(() => {
  if (!restaurantsError.value) return '';
  return 'Не удалось загрузить рестораны. Попробуйте обновить страницу.';
});
const categoriesErrorMessage = computed(() => {
  if (!categoriesError.value) return '';
  return 'Не удалось загрузить категории. Фильтр временно недоступен.';
});
const restaurantsEmptyMessage = computed(() => {
  if (selectedCategoryId.value === null) {
    return 'Рестораны пока не добавлены';
  }

  return `В категории «${activeCategoryName.value}» пока нет доступных ресторанов`;
});

const selectCategory = async (categoryId: string | number | null) => {
  if (selectedCategoryId.value === categoryId) {
    return;
  }

  selectedCategoryId.value = typeof categoryId === 'string' ? Number(categoryId) : categoryId;

  try {
    await refreshRestaurants();
  } catch {
  }
};

const goToRestaurant = async (slug: string) => {
  try {
    await navigateTo(`/restaurants/${slug}`);
  } catch {
    $notify?.failure?.('Не удалось открыть ресторан');
  }
};
</script>

<template>
  <div class="page">
    <div class="page__container">
      <template v-if="restaurantsError">
        <section class="cards">
          <div class="cards__header">
            <div class="cards__title-block">
              <h1 class="cards__title">
                Рестораны рядом
              </h1>
              <p class="cards__subtitle">
                Выберите ресторан, чтобы посмотреть меню и оформить заказ
              </p>
            </div>
          </div>

          <div class="state-message state-message--error">
            {{ restaurantsErrorMessage }}
          </div>
        </section>
      </template>

      <CardsLayout
          v-else
          title="Рестораны рядом"
          subtitle="Выберите ресторан, чтобы посмотреть меню и оформить заказ"
          :loading="restaurantsPending"
          :has-items="hasRestaurants"
      >
        <template #before-content>
          <div class="cards__filters-wrap">
            <BaseScrollableTabs
                :model-value="selectedCategoryId"
                :items="categoryFilters.map(c => ({ id: c.id, label: c.name }))"
                :skeleton="categoriesPending"
                @update:model-value="selectCategory"
            />

            <div
                v-if="categoriesErrorMessage"
                class="state-message state-message--error cards__filters-message"
            >
              {{ categoriesErrorMessage }}
            </div>
          </div>
        </template>

        <RestaurantCard
            v-for="restaurant in restaurants"
            :key="restaurant.id"
            :restaurant="restaurant"
            @click="goToRestaurant(restaurant.slug)"
        />

        <template #empty>
          {{ restaurantsEmptyMessage }}
        </template>
      </CardsLayout>
    </div>
  </div>
</template>

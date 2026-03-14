<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import { useManageRestaurantsPage } from '~/composables/useManageRestaurantsPage';

const {
  items,
  loading,
  errorMessage,
  hasRestaurants,
  goToDashboard,
  goToPublic,
  formatRestaurantAddress,
  formatRestaurantPrepTime,
  getRestaurantActivityLabel,
  getRestaurantActivityStatus,
} = useManageRestaurantsPage();
</script>

<template>
  <section class="restaurants-manage">
    <div class="restaurants-manage__container container">
      <button
          type="button"
          class="restaurants-manage__back page-back"
          @click="$router.back()"
      >
        <ArrowLeft class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
        <span>Назад</span>
      </button>

      <h1 class="restaurants-manage__title">
        Мои рестораны
      </h1>

      <p class="restaurants-manage__subtitle">
        Здесь в будущем будет дашборд владельца: управление меню, заказами и персоналом.
      </p>

      <div
          v-if="loading"
          class="restaurants-manage__loading"
      >
        Загрузка ресторанов...
      </div>

      <div
          v-else-if="errorMessage"
          class="restaurants-manage__error"
      >
        {{ errorMessage }}
      </div>

      <div
          v-else-if="!hasRestaurants"
          class="restaurants-manage__empty"
      >
        У вас пока нет ресторанов.
      </div>

      <div
          v-else
          class="restaurants-manage__list"
      >
        <article
            v-for="r in items"
            :key="r.id"
            class="restaurants-manage__card"
        >
          <div class="restaurants-manage__card-main">
            <h2 class="restaurants-manage__card-title">
              {{ r.name }}
            </h2>
            <p class="restaurants-manage__card-address">
              {{ formatRestaurantAddress(r) }}
            </p>

            <div class="restaurants-manage__card-meta">
              <span
                  class="restaurants-manage__status"
                  :data-status="getRestaurantActivityStatus(r.is_active)"
              >
                {{ getRestaurantActivityLabel(r.is_active) }}
              </span>

              <span
                  v-if="formatRestaurantPrepTime(r)"
                  class="restaurants-manage__prep"
              >
                {{ formatRestaurantPrepTime(r) }}
              </span>
            </div>
          </div>

          <div class="restaurants-manage__card-actions">
            <button
                type="button"
                class="button restaurants-manage__btn"
                @click="goToDashboard(r.slug)"
            >
              Дашборд
            </button>
            <button
                type="button"
                class="restaurants-manage__link"
                @click="goToPublic(r.slug)"
            >
              Открыть витрину
            </button>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

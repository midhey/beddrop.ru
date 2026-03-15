<script setup lang="ts">
import {
  ArrowLeft,
  Clock3,
  ExternalLink,
  MapPin,
  Store,
} from 'lucide-vue-next';
import { useManageRestaurantsPage } from '~/composables/useManageRestaurantsPage';

const {
  items,
  loading,
  errorMessage,
  hasRestaurants,
  activeCount,
  inactiveCount,
  goToDashboard,
  goToPublic,
  getRestaurantInitial,
  formatCreatedDate,
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

      <div class="restaurants-manage__hero">
        <div class="restaurants-manage__hero-main">
          <span class="restaurants-manage__eyebrow">Кабинет ресторана</span>
          <h1 class="restaurants-manage__title">
            Мои рестораны
          </h1>

          <p class="restaurants-manage__subtitle">
            Выберите ресторан, чтобы перейти в рабочий дашборд: обновить меню, открыть витрину и держать под контролем статус точки.
          </p>
        </div>

        <div class="restaurants-manage__hero-stats">
          <div class="restaurants-manage__hero-stat">
            <span class="restaurants-manage__hero-stat-label">Всего точек</span>
            <strong class="restaurants-manage__hero-stat-value">{{ items.length }}</strong>
          </div>
          <div class="restaurants-manage__hero-stat">
            <span class="restaurants-manage__hero-stat-label">Активных</span>
            <strong class="restaurants-manage__hero-stat-value">{{ activeCount }}</strong>
          </div>
          <div class="restaurants-manage__hero-stat">
            <span class="restaurants-manage__hero-stat-label">На паузе</span>
            <strong class="restaurants-manage__hero-stat-value">{{ inactiveCount }}</strong>
          </div>
        </div>
      </div>

      <div class="restaurants-manage__summary">
        <article class="restaurants-manage__summary-card">
          <span class="restaurants-manage__summary-label">Что доступно</span>
          <p class="restaurants-manage__summary-text">
            Управление меню, статусом ресторана, персоналом и быстрый переход к публичной витрине.
          </p>
        </article>

        <article class="restaurants-manage__summary-card restaurants-manage__summary-card--accent">
          <span class="restaurants-manage__summary-label">Текущий фокус</span>
          <p class="restaurants-manage__summary-text">
            Сначала выбираешь точку, затем работаешь уже в её отдельном кабинете без лишней навигации.
          </p>
        </article>
      </div>

      <div
          v-if="loading"
          class="restaurants-manage__state restaurants-manage__state--loading"
      >
        <div class="restaurants-manage__state-icon">
          <Store class="ui-icon" :size="24" :stroke-width="1.9" aria-hidden="true" />
        </div>
        <div>
          <h2 class="restaurants-manage__state-title">Загружаем рестораны</h2>
          <p class="restaurants-manage__state-text">Подтягиваем список точек и их текущие статусы.</p>
        </div>
      </div>

      <div
          v-else-if="errorMessage"
          class="restaurants-manage__state restaurants-manage__state--error"
      >
        <div class="restaurants-manage__state-icon">
          <Store class="ui-icon" :size="24" :stroke-width="1.9" aria-hidden="true" />
        </div>
        <div>
          <h2 class="restaurants-manage__state-title">Не удалось загрузить список</h2>
          <p class="restaurants-manage__state-text">{{ errorMessage }}</p>
        </div>
      </div>

      <div
          v-else-if="!hasRestaurants"
          class="restaurants-manage__state restaurants-manage__state--empty"
      >
        <div class="restaurants-manage__state-icon">
          <Store class="ui-icon" :size="24" :stroke-width="1.9" aria-hidden="true" />
        </div>
        <div>
          <h2 class="restaurants-manage__state-title">Пока нет ресторанов</h2>
          <p class="restaurants-manage__state-text">
            Когда у аккаунта появятся точки, они будут показаны здесь с быстрым переходом в управление.
          </p>
        </div>
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
          <div class="restaurants-manage__card-top">
            <div class="restaurants-manage__card-brand">
              <span class="restaurants-manage__card-logo">
                {{ getRestaurantInitial(r.name) }}
              </span>

              <div class="restaurants-manage__card-main">
                <div class="restaurants-manage__card-heading">
                  <h2 class="restaurants-manage__card-title">
                    {{ r.name }}
                  </h2>
                  <span
                      class="restaurants-manage__status"
                      :data-status="getRestaurantActivityStatus(r.is_active)"
                  >
                    {{ getRestaurantActivityLabel(r.is_active) }}
                  </span>
                </div>

                <p class="restaurants-manage__card-address">
                  <MapPin class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
                  <span>{{ formatRestaurantAddress(r) }}</span>
                </p>
              </div>
            </div>

            <button
                type="button"
                class="restaurants-manage__ghost-link"
                @click="goToPublic(r.slug)"
            >
              <ExternalLink class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
              <span>Открыть витрину</span>
            </button>
          </div>

          <div class="restaurants-manage__card-grid">
            <div
                v-if="formatRestaurantPrepTime(r)"
                class="restaurants-manage__metric"
            >
              <span class="restaurants-manage__metric-icon">
                <Clock3 class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
              </span>
              <div>
                <span class="restaurants-manage__metric-label">Время приготовления</span>
                <strong class="restaurants-manage__metric-value">{{ formatRestaurantPrepTime(r) }}</strong>
              </div>
            </div>

            <div class="restaurants-manage__metric">
              <span class="restaurants-manage__metric-icon">
                <Store class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
              </span>
              <div>
                <span class="restaurants-manage__metric-label">Создан</span>
                <strong class="restaurants-manage__metric-value">
                  {{ formatCreatedDate(r.created_at) || 'Дата не указана' }}
                </strong>
              </div>
            </div>
          </div>

          <div class="restaurants-manage__card-actions">
            <button
                type="button"
                class="button restaurants-manage__btn"
                @click="goToDashboard(r.slug)"
            >
              Открыть кабинет
            </button>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

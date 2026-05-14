<script setup lang="ts">
import { computed } from 'vue';
import { ArrowRight } from 'lucide-vue-next';
import { useRouter, useRoute } from '#app';
import {
  getActiveOrderBannerStatusClass,
  getOrderStatusLabel,
  isFinalOrderStatus,
} from '~/domains/orders/presentation';
import { formatPrice } from '~/utils/formatting';

const appShellStore = useAppShellStore();
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

// скрываем баннер на страницах самого заказа, чтобы не дублировать
const shouldHideOnRoute = computed(() => {
  return route.path.startsWith('/orders/');
});

const visible = computed(() => {
  if (shouldHideOnRoute.value) return false;
  if (appShellStore.activeOrderLoading) return false;
  if (!appShellStore.activeOrder) return false;
  // если заказ уже финальный — тоже не показываем
  return !isFinalOrderStatus(appShellStore.activeOrder.status);
});

const loading = computed(() => {
  if (shouldHideOnRoute.value) return false;
  if (!authStore.isReady) return true;
  if (!authStore.isAuthenticated) return false;

  return appShellStore.activeOrderLoading || !appShellStore.bootstrappedForAuth;
});

const goToOrder = () => {
  if (!appShellStore.activeOrder) return;
  router.push(`/orders/${appShellStore.activeOrder.id}`);
};
</script>

<template>
  <ClientOnly>
    <div
        v-if="loading"
        class="active-order-banner active-order-banner--loading"
        aria-hidden="true"
    >
      <div class="active-order-banner__container">
        <div class="active-order-banner__inner active-order-banner__inner--skeleton">
          <div class="active-order-banner__left">
            <span class="active-order-banner__skeleton-title skeleton" />
            <span class="active-order-banner__skeleton-meta skeleton" />
          </div>

          <div class="active-order-banner__right">
            <span class="active-order-banner__skeleton-price skeleton" />
            <span class="active-order-banner__skeleton-cta skeleton" />
          </div>
        </div>
      </div>
    </div>

    <div
        v-else-if="visible && appShellStore.activeOrder"
        class="active-order-banner "
    >
      <div class="active-order-banner__container">
        <button
            type="button"
            class="active-order-banner__inner"
            @click="goToOrder"
        >
          <div class="active-order-banner__left">
            <div class="active-order-banner__title">
              <span class="active-order-banner__dot" />
              <span>Активный заказ #{{ appShellStore.activeOrder.id }}</span>
            </div>

            <div class="active-order-banner__info">
            <span class="active-order-banner__restaurant">
              {{ appShellStore.activeOrder.restaurant?.name || 'Ресторан' }}
            </span>

              <span
                  class="active-order-banner__status"
                  :class="getActiveOrderBannerStatusClass(appShellStore.activeOrder.status)"
              >
              {{ getOrderStatusLabel(appShellStore.activeOrder.status, 'banner') }}
            </span>

              <span class="active-order-banner__count">
              {{ appShellStore.activeOrder.items_count ?? '—' }} позиций
            </span>
            </div>
          </div>

          <div class="active-order-banner__right">
          <span class="active-order-banner__price">
            {{ formatPrice(appShellStore.activeOrder.total_price) }}
          </span>
            <span class="active-order-banner__cta">
            <span>Отслеживать</span>
            <ArrowRight class="ui-icon" :size="14" :stroke-width="1.9" aria-hidden="true" />
          </span>
          </div>
        </button>

      </div>
    </div>

    <template #fallback>
      <div
          v-if="!shouldHideOnRoute"
          class="active-order-banner active-order-banner--loading"
          aria-hidden="true"
      >
        <div class="active-order-banner__container">
          <div class="active-order-banner__inner active-order-banner__inner--skeleton">
            <div class="active-order-banner__left">
              <span class="active-order-banner__skeleton-title skeleton" />
              <span class="active-order-banner__skeleton-meta skeleton" />
            </div>

            <div class="active-order-banner__right">
              <span class="active-order-banner__skeleton-price skeleton" />
              <span class="active-order-banner__skeleton-cta skeleton" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </ClientOnly>
</template>

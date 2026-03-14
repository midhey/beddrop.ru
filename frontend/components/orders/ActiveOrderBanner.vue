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

const goToOrder = () => {
  if (!appShellStore.activeOrder) return;
  router.push(`/orders/${appShellStore.activeOrder.id}`);
};
</script>

<template>
  <div
      v-if="visible && appShellStore.activeOrder"
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
          Отслеживать
          <ArrowRight class="ui-icon" :size="14" :stroke-width="1.9" aria-hidden="true" />
        </span>
        </div>
      </button>

    </div>
  </div>
</template>

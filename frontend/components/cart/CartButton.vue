<script setup lang="ts">
import { computed } from 'vue';
import { ShoppingBasket } from 'lucide-vue-next';
import { useRouter } from '#app';
import { useAuthStore } from '~/stores/auth';
import { useCartStore } from '~/stores/cart';

const authStore = useAuthStore();
const isAuthenticated = computed(() => authStore.isAuthenticated);

const cartStore = useCartStore();
const router = useRouter();

const emit = defineEmits<{
  (e: 'navigate'): void;
}>();

const totalCount = computed(() => cartStore.totalCount);

const goToCart = async () => {
  await router.push('/cart');
  emit('navigate');
};
</script>

<template>
  <button
      v-if="isAuthenticated"
      type="button"
      class="header__cart"
      aria-label="Открыть корзину"
      @click="goToCart"
  >
    <ShoppingBasket
        class="header__cart-icon ui-icon"
        :size="32"
        :stroke-width="1.9"
        aria-hidden="true"
    />
    <span class="header__cart-label">
      Корзина
    </span>
    <span
        v-if="totalCount > 0"
        class="header__cart-badge"
    >
      {{ totalCount }}
    </span>
  </button>
</template>

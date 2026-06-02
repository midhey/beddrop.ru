<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AuthMenu from '~/components/auth/AuthMenu.vue';
import CartButton from '~/components/cart/CartButton.vue';
import { useBurgerMenu } from '~/composables/useBurgerMenu';
import logoSrc from '~/assets/images/logo.webp';

const shellRef = ref<HTMLElement | null>(null);
const isFloating = ref(false);

const {
  isOpen,
  burgerRef,
  menuRef,
  toggleBurger,
  closeBurger,
} = useBurgerMenu({
  breakpoint: 992,
});

let resizeObserver: ResizeObserver | null = null;
let scrollListener: (() => void) | null = null;
let resizeListener: (() => void) | null = null;
let rafId = 0;

const setShellHeight = () => {
  const shellEl = shellRef.value;
  const headerEl = burgerRef.value;
  if (!shellEl || !headerEl) return;

  const height = headerEl.offsetHeight;
  shellEl.style.setProperty('--header-shell-height', `${height}px`);
  document.documentElement.style.setProperty('--header-h', `${height}px`);
};

const getFloatingThreshold = () => {
  if (typeof window === 'undefined') return Number.MAX_SAFE_INTEGER;

  const headerHeight = shellRef.value?.offsetHeight ?? burgerRef.value?.offsetHeight ?? 0;
  const activeBannerHeight = document.querySelector<HTMLElement>('.active-order-banner')?.offsetHeight ?? 0;

  return Math.max(headerHeight + activeBannerHeight - 8, 0);
};

const syncFloatingState = () => {
  if (typeof window === 'undefined') return;
  if (isOpen.value) return;

  const scrollTop = window.scrollY || window.pageYOffset || 0;
  const headerHeight = burgerRef.value?.offsetHeight ?? 0;
  const threshold = getFloatingThreshold();
  const hysteresis = Math.max(24, Math.round(headerHeight * 0.35));
  const nextState = isFloating.value
    ? scrollTop > Math.max(threshold - hysteresis, 0)
    : scrollTop > threshold;

  if (nextState !== isFloating.value) {
    isFloating.value = nextState;
  }
};

const syncHeaderMetrics = () => {
  setShellHeight();
  syncFloatingState();
};

const scheduleFloatingSync = () => {
  if (typeof window === 'undefined') return;
  if (rafId) return;

  rafId = window.requestAnimationFrame(() => {
    rafId = 0;
    syncFloatingState();
  });
};

watch(isFloating, async () => {
  await nextTick();
  setShellHeight();
});

watch(isOpen, async (open) => {
  await nextTick();
  setShellHeight();

  if (!open) {
    syncFloatingState();
  }
});

onMounted(async () => {
  if (typeof window === 'undefined') return;

  await nextTick();
  syncHeaderMetrics();

  scrollListener = () => {
    scheduleFloatingSync();
  };
  resizeListener = () => {
    syncHeaderMetrics();
  };

  window.addEventListener('scroll', scrollListener, { passive: true });
  window.addEventListener('resize', resizeListener, { passive: true });

  if (typeof ResizeObserver !== 'undefined' && burgerRef.value) {
    resizeObserver = new ResizeObserver(() => {
      syncHeaderMetrics();
    });
    resizeObserver.observe(burgerRef.value);
  }
});

onBeforeUnmount(() => {
  if (typeof window === 'undefined') return;

  if (rafId) {
    window.cancelAnimationFrame(rafId);
  }

  if (scrollListener) {
    window.removeEventListener('scroll', scrollListener);
  }

  if (resizeListener) {
    window.removeEventListener('resize', resizeListener);
  }

  resizeObserver?.disconnect();
});
</script>

<template>
  <div
      ref="shellRef"
      class="header-shell"
  >
    <header
        ref="burgerRef"
        class="header"
        :class="{ 'header--floating': isFloating }"
    >
      <div
          class="header__container"
          :data-burger="isOpen ? 'open' : 'close'"
      >
        <div class="header__block">
          <NuxtLink to="/" class="header__logo title-1" @click="closeBurger">
            <img :src="logoSrc" alt="BedDrop" class="header__logo-img">
          </NuxtLink>
        </div>

        <button
            type="button"
            data-burger-button
            @click="toggleBurger"
            aria-label="Меню"
            :aria-expanded="isOpen"
        >
          <span data-burger-icon></span>
        </button>

        <div
            class="header__block"
            data-burger-menu
            ref="menuRef"
        >
          <CartButton @navigate="closeBurger" />
          <AuthMenu
            @navigate="closeBurger"
            @logout-done="closeBurger"
          />
        </div>
      </div>
    </header>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AuthMenu from '~/components/auth/AuthMenu.vue';
import CartButton from '~/components/cart/CartButton.vue';
import { useBurgerMenu } from '~/composables/useBurgerMenu';

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
          <NuxtLink to="/" @click="closeBurger" class="header__logo title-1">
            <svg width="200" height="60" viewBox="0 0 260 80" xmlns="http://www.w3.org/2000/svg">
              <g transform="translate(0, 0)">
                <circle cx="40" cy="40" r="32" fill="#7A3BFF" />
                <rect x="16" y="38" width="48" height="16" rx="6" fill="#FFFFFF" />
                <rect x="18" y="30" width="20" height="8" rx="4" fill="#FFFFFF" />
                <circle cx="48" cy="28" r="4" fill="#FFFFFF" />
              </g>
              <text x="88" y="47"
                    font-family="Unbounded, OpenSans, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
                    font-size="28"
                    font-weight="600"
                    fill="#111111">
                BedDrop
              </text>
            </svg>
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

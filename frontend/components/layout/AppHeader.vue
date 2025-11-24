<script setup lang="ts">
import AuthMenu from '~/components/auth/AuthMenu.vue';
import { useBurgerMenu } from '~/composables/useBurgerMenu';

const {
  isOpen,
  burgerRef,
  menuRef,
  toggleBurger,
  closeBurger,
} = useBurgerMenu({
  breakpoint: 768,
});
</script>

<template>
  <header class="header" ref="burgerRef">
    <div
        class="header__container"
        :data-burger="isOpen ? 'open' : 'close'"
    >
      <div class="header__block">
        <NuxtLink to="/" @click="closeBurger" class="header__logo title-1">
          <svg width="260" height="80" viewBox="0 0 260 80" xmlns="http://www.w3.org/2000/svg">
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
        <AuthMenu
          @navigate="closeBurger"
          @logout-done="closeBurger"
        />
      </div>
    </div>

    <button
        type="button"
        data-burger-backdrop
        @click="closeBurger"
        aria-hidden="true"
    ></button>
  </header>
</template>
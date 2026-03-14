<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
  Bike,
  ChevronDown,
  LogOut,
  ReceiptText,
  Store,
  UserRound,
} from 'lucide-vue-next';
import { useRouter } from '#app';
import BaseModal from '~/components/ui/BaseModal.vue';
import LoginForm from '~/components/auth/LoginForm.vue';
import RegisterForm from '~/components/auth/RegisterForm.vue';

const authStore = useAuthStore();
const appShellStore = useAppShellStore();
const router = useRouter();

const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);
const isUserMenuOpen = ref(false);
const isBurgerLayout = ref(false);

const emit = defineEmits<{
  (e: 'navigate'): void;
  (e: 'logout-done'): void;
}>();

const openLogin = () => {
  isRegisterOpen.value = false;
  isLoginOpen.value = true;
};

const openRegister = () => {
  isLoginOpen.value = false;
  isRegisterOpen.value = true;
};

const closeLogin = () => {
  isLoginOpen.value = false;
};

const closeRegister = () => {
  isRegisterOpen.value = false;
};

const switchToRegister = () => {
  isLoginOpen.value = false;
  isRegisterOpen.value = true;
};

const syncBurgerLayout = () => {
  if (typeof window === 'undefined') {
    return;
  }

  isBurgerLayout.value = window.innerWidth <= 992;

  if (isBurgerLayout.value) {
    isUserMenuOpen.value = true;
  }
};

const toggleUserMenu = () => {
  if (isBurgerLayout.value) {
    return;
  }

  isUserMenuOpen.value = !isUserMenuOpen.value;
};

const closeUserMenu = () => {
  isUserMenuOpen.value = false;
};

const handleNavigate = () => {
  closeUserMenu();
  emit('navigate');
};

const logout = async () => {
  await authStore.logout();
  isUserMenuOpen.value = false;
  appShellStore.resetForGuest();
  emit('logout-done');
  await router.push('/');
};

const userName = computed(() => {
  return authStore.user?.name || authStore.user?.email || 'Пользователь';
});

const userEmail = computed(() => {
  return authStore.user?.email || 'Аккаунт BedDrop';
});

const userInitial = computed(() => {
  const src = authStore.user?.name || authStore.user?.email || 'U';
  return src.trim().charAt(0).toUpperCase();
});

const isDropdownOpen = computed(() => isBurgerLayout.value || isUserMenuOpen.value);

let resizeListener: (() => void) | null = null;

onMounted(() => {
  if (typeof window === 'undefined') {
    return;
  }

  syncBurgerLayout();

  resizeListener = () => {
    syncBurgerLayout();
  };

  window.addEventListener('resize', resizeListener, { passive: true });
});

onBeforeUnmount(() => {
  if (typeof window === 'undefined') {
    return;
  }

  if (resizeListener) {
    window.removeEventListener('resize', resizeListener);
  }
});
</script>

<template>
  <div class="header__menu">
    <ClientOnly>
      <template v-if="!authStore.isAuthenticated">
        <button
            type="button"
            class="button header__button"
            @click="openLogin"
        >
          Войти
        </button>
      </template>

      <template v-else>
        <div
            class="header__dropdown"
            :data-dropdown="isDropdownOpen ? 'open' : 'close'"
            :class="{ 'header__dropdown--expanded': isBurgerLayout }"
        >
          <button
              type="button"
              class="header__dropdown-block"
              data-dropdown-toggle
              @click.stop="toggleUserMenu"
          >
            <span class="header__avatar">
              {{ userInitial }}
            </span>
            <span class="header__user-meta">
              <span class="header__user-name">
                {{ userName }}
              </span>
              <span class="header__user-caption">
                Личный кабинет
              </span>
            </span>
            <ChevronDown
                v-if="!isBurgerLayout"
                class="header__dropdown-chevron ui-icon"
                :size="18"
                :stroke-width="1.9"
                aria-hidden="true"
            />
          </button>

          <div
              class="header__dropdown-list"
              data-dropdown-menu
          >
            <div class="header__dropdown-summary">
              <span class="header__avatar header__avatar--large">
                {{ userInitial }}
              </span>
              <div class="header__dropdown-summary-copy">
                <span class="header__dropdown-title">
                  {{ userName }}
                </span>
                <span class="header__dropdown-subtitle">
                  {{ userEmail }}
                </span>
              </div>
            </div>

            <NuxtLink
                to="/profile"
                class="header__dropdown-item"
                @click="handleNavigate"
            >
              <span class="header__dropdown-item-icon">
                <UserRound class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
              </span>
              <span class="header__dropdown-item-copy">
                <span class="header__dropdown-item-title">
                  Профиль
                </span>
                <span class="header__dropdown-item-subtitle">
                  Аккаунт, адреса и настройки
                </span>
              </span>
            </NuxtLink>

            <NuxtLink
                to="/orders"
                class="header__dropdown-item"
                @click="handleNavigate"
            >
              <span class="header__dropdown-item-icon">
                <ReceiptText class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
              </span>
              <span class="header__dropdown-item-copy">
                <span class="header__dropdown-item-title">
                  Мои заказы
                </span>
                <span class="header__dropdown-item-subtitle">
                  Текущие и завершённые заказы
                </span>
              </span>
            </NuxtLink>

            <NuxtLink
                v-if="appShellStore.hasRestaurantsAccess"
                to="/restaurants/manage"
                class="header__dropdown-item"
                @click="handleNavigate"
            >
              <span class="header__dropdown-item-icon">
                <Store class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
              </span>
              <span class="header__dropdown-item-copy">
                <span class="header__dropdown-item-title">
                  Мои рестораны
                </span>
                <span class="header__dropdown-item-subtitle">
                  Меню, сотрудники и заказы
                </span>
              </span>
            </NuxtLink>

            <NuxtLink
                v-if="appShellStore.hasCourierAccess"
                to="/courier"
                class="header__dropdown-item"
                @click="handleNavigate"
            >
              <span class="header__dropdown-item-icon">
                <Bike class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
              </span>
              <span class="header__dropdown-item-copy">
                <span class="header__dropdown-item-title">
                  Курьерский кабинет
                </span>
                <span class="header__dropdown-item-subtitle">
                  Смены, доставки и доход
                </span>
              </span>
            </NuxtLink>

            <button
                type="button"
                class="header__dropdown-item header__dropdown-item--danger"
                @click="logout"
            >
              <span class="header__dropdown-item-icon">
                <LogOut class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
              </span>
              <span class="header__dropdown-item-copy">
                <span class="header__dropdown-item-title">
                  Выйти
                </span>
                <span class="header__dropdown-item-subtitle">
                  Завершить текущую сессию
                </span>
              </span>
            </button>
          </div>
        </div>
      </template>

      <BaseModal v-model="isLoginOpen" title="Вход в аккаунт">
        <LoginForm
            @success="
            closeLogin();
            handleNavigate();
          "
            @open-register="switchToRegister"
        />
      </BaseModal>

      <BaseModal v-model="isRegisterOpen" title="Регистрация">
        <RegisterForm
            @success="
            closeRegister();
            handleNavigate();
          "
        />
      </BaseModal>
    </ClientOnly>
  </div>
</template>

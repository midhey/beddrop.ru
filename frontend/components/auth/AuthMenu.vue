<script setup lang="ts">
import BaseModal from "~/components/ui/BaseModal.vue";
import LoginForm from "~/components/auth/LoginForm.vue";
import RegisterForm from "~/components/auth/RegisterForm.vue";

const authStore = useAuthStore();
const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);
const isUserMenuOpen = ref(false);

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

const toggleUserMenu = () => {
  isUserMenuOpen.value = !isUserMenuOpen.value;
};

const closeUserMenu = () => {
  isUserMenuOpen.value = false;
};

const logout = async () => {
  await authStore.logout();
  isUserMenuOpen.value = false;
};

const userName = computed(() => {
  return authStore.user?.name || authStore.user?.email || 'Пользователь';
});

const userInitial = computed(() => {
  const src = authStore.user?.name || authStore.user?.email || 'U';
  return src.trim().charAt(0).toUpperCase();
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
            :data-dropdown="isUserMenuOpen ? 'open' : 'close'"
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
          </button>

          <div
              class="header__dropdown-list"
              data-dropdown-menu
          >
            <NuxtLink
                to="/profile"
                class="header__dropdown-item"
                @click="closeUserMenu"
            >
              <span class="header__username">
                {{ userName }}
              </span>
              <span class="header__profile-btn">
                Профиль
              </span>
            </NuxtLink>
            <NuxtLink
                to="/orders"
                class="header__dropdown-item"
                @click="closeUserMenu"
            >
              Мои заказы
            </NuxtLink>
            <button
                type="button"
                class="header__dropdown-item header__user-item--danger"
                @click="logout"
            >
              Выйти
            </button>
          </div>
        </div>
      </template>

      <BaseModal v-model="isLoginOpen" title="Вход в аккаунт">
        <LoginForm @success="closeLogin" @open-register="switchToRegister"  />
      </BaseModal>

      <BaseModal v-model="isRegisterOpen" title="Регистрация">
        <RegisterForm @success="closeRegister" />
      </BaseModal>
    </ClientOnly>
  </div>
</template>
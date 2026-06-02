<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next';
import { useFeedback } from '~/composables/useFeedback';

const authStore = useAuthStore();
const feedback = useFeedback();

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const isLoading = computed(() => authStore.loading);

const emit = defineEmits<{
  (e: 'success'): void;
  (e: 'open-register'): void;
}>();

const onSubmit = async () => {
  try {
    await feedback.withBlock('.modal', async () => {
      await authStore.login({
        email: email.value,
        password: password.value,
      });
    });
    emit('success');
  } catch {
  }
};

const onOpenRegister = () => {
  emit('open-register');
};
</script>

<template>
  <form
      class="form"
      @submit.prevent="onSubmit"
  >
    <div class="form__description">
      Нет аккаунта?
      <button
          type="button"
          class="button button--text"
          @click="onOpenRegister"
      >
        Зарегистрироваться
      </button>
    </div>

    <div class="form__field">
      <label class="form__label">
        <input
            v-model="email"
            type="email"
            class="form__input"
            placeholder="Электронная почта"
            required
            autocomplete="email"
        >
        <span class="form__label-text">Электронная почта</span>
      </label>
    </div>

    <div class="form__field">
      <label class="form__label">
        <input
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            class="form__input"
            placeholder="Пароль"
            required
            autocomplete="current-password"
        >
        <span class="form__label-text">Пароль</span>
        <button
            type="button"
            class="form__password-toggle"
            @click="showPassword = !showPassword"
            :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
        >
          <Eye v-if="!showPassword" class="ui-icon" />
          <EyeOff v-else class="ui-icon" />
        </button>
      </label>
    </div>

    <button
        class="form__button button"
        :disabled="isLoading"
    >
      {{ isLoading ? 'Входим...' : 'Отправить' }}
    </button>
  </form>
</template>

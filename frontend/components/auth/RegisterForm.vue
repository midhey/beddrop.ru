<script setup lang="ts">
import { useFeedback } from '~/composables/useFeedback';
import {
  isCompletePhoneInput,
  normalizePhoneInput,
  PHONE_MASK_OPTIONS,
} from '~/utils/phone';

const authStore = useAuthStore();
const feedback = useFeedback();

const email = ref('');
const phone = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const name = ref('');
const isLoading = computed(() => authStore.loading);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const onSubmit = async () => {
  if (password.value !== passwordConfirmation.value) {
    feedback.failure('Пароли не совпадают');
    return;
  }

  const rawPhone = normalizePhoneInput(phone.value);

  if (!isCompletePhoneInput(phone.value)) {
    feedback.failure('Введите номер телефона полностью');
    return;
  }

  try {
    await feedback.withBlock('.modal', async () => {
      await authStore.register({
        email: email.value,
        phone: rawPhone,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
        name: name.value || null,
      });
    });

    emit('success');
  } catch {
  }
};

</script>

<template>
  <form
      class="form"
      @submit.prevent="onSubmit"
  >
    <div class="form__field">
      <label class="form__label">
        <input
            v-model="name"
            type="text"
            class="form__input"
            placeholder="Имя"
            required
            autocomplete="name"
        >
        <span class="form__label-text">Имя</span>
      </label>
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
            v-model="phone"
            v-imask="PHONE_MASK_OPTIONS"
            type="tel"
            class="form__input"
            placeholder="Телефон"
            required
            autocomplete="tel"
        >
        <span class="form__label-text">Телефон</span>
      </label>
    </div>

    <div class="form__field">
      <label class="form__label">
        <input
            v-model="password"
            type="password"
            class="form__input"
            placeholder="Пароль"
            required
            autocomplete="new-password"
        >
        <span class="form__label-text">Пароль</span>
      </label>
    </div>

    <div class="form__field">
      <label class="form__label">
        <input
            v-model="passwordConfirmation"
            type="password"
            class="form__input"
            placeholder="Подтверждение пароля"
            required
            autocomplete="new-password"
        >
        <span class="form__label-text">Повторите пароль</span>
      </label>
    </div>

    <button
        class="form__button button"
        :disabled="isLoading"
    >
      {{ isLoading ? 'Регистрируем...' : 'Отправить' }}
    </button>
  </form>
</template>

<script setup lang="ts">
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const isLoading = computed(() => authStore.loading);

const formRef = ref<HTMLFormElement | null>(null);

const emit = defineEmits<{
  (e: 'success'): void;
  (e: 'open-register'): void;
}>();

const onSubmit = async () => {
  const { $block } = useNuxtApp();

  if (formRef.value) {
    $block?.circle('.modal', 'Отправка...');
  }

  try {
    await authStore.login({
      email: email.value,
      password: password.value,
    });
    emit('success');
  } catch {
  } finally {
    if (formRef.value) {
      $block?.remove(formRef.value);
    }
  }
};

const onOpenRegister = () => {
  emit('open-register');
};
</script>

<template>
  <form
      ref="formRef"
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
            type="password"
            class="form__input"
            placeholder="Пароль"
            required
            autocomplete="current-password"
        >
        <span class="form__label-text">Пароль</span>
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
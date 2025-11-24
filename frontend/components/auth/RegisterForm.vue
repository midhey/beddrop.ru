<script setup lang="ts">

const authStore = useAuthStore();

const email = ref('');
const phone = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const name = ref('');
const isLoading = computed(() => authStore.loading);

const phoneMask = {
  mask: '+{7} 000 000-00-00',
  lazy: false,
  overwrite: true,
};

const formRef = ref<HTMLFormElement | null>(null);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const onSubmit = async () => {
  const { $block, $notify } = useNuxtApp();

  if (password.value !== passwordConfirmation.value) {
    $notify?.failure?.('Пароли не совпадают');
    return;
  }

  if (formRef.value) {
    $block?.circle('.modal', 'Отправка...');
  }

  try {
    await authStore.register({
      email: email.value,
      phone: phone.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      name: name.value || null,
    });
    emit('success');
  } catch (e) {
    console.error('[RegisterForm] submit error', e);
  } finally {
    if (formRef.value) {
      $block?.remove(formRef.value);
    }
  }
}

</script>

<template>
  <form
      ref="formRef"
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
            v-imask="phoneMask"
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
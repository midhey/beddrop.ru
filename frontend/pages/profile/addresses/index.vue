<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { useSeoMeta } from '#imports';
import AddressFields from '~/components/address/AddressFields.vue';
import { useAddresses } from '~/composables/useAddresses';
import { useFeedback } from '~/composables/useFeedback';

const {
  items: addresses,
  loading,
  errorMessage,
  fetchAddresses,
  createAddress,
  updateAddress,
  deleteAddress,
} = useAddresses();
const feedback = useFeedback();

useSeoMeta({
  title: 'Мои адреса — BedDrop',
});

const formMode = ref<'create' | 'edit'>('create');
const editingId = ref<number | null>(null);

const form = ref({
  label: '',
  line1: '',
  line2: '',
  city: '',
  postal_code: '',
});

const resetForm = () => {
  formMode.value = 'create';
  editingId.value = null;
  form.value = {
    label: '',
    line1: '',
    line2: '',
    city: '',
    postal_code: '',
  };
};

const startCreate = () => {
  resetForm();
};

const startEdit = (addr: any) => {
  formMode.value = 'edit';
  editingId.value = addr.id;
  form.value = {
    label: addr.label ?? '',
    line1: addr.line1 ?? '',
    line2: addr.line2 ?? '',
    city: addr.city ?? '',
    postal_code: addr.postal_code ?? '',
  };
};

const submit = async () => {
  if (!form.value.line1.trim()) {
    return;
  }

  const payload = {
    label: form.value.label || null,
    line1: form.value.line1,
    line2: form.value.line2 || null,
    city: form.value.city || null,
    postal_code: form.value.postal_code || null,
  };

  if (formMode.value === 'create') {
    await feedback.withBlock('.addresses-page__form-card', async () => {
      await createAddress(payload);
      resetForm();
    }, 'Сохраняем адрес...');
  } else if (formMode.value === 'edit' && editingId.value) {
    await feedback.withBlock('.addresses-page__form-card', async () => {
      await updateAddress(editingId.value, payload);
    }, 'Обновляем адрес...');
  }
};

const handleDelete = async (id: number) => {
  const confirmed = await feedback.confirm({
    title: 'Удалить адрес',
    message: 'Удалить этот адрес?',
    confirmText: 'Удалить',
    cancelText: 'Отмена',
  });

  if (!confirmed) return;

  await feedback.withBlock('.addresses-page__list-card', async () => {
    await deleteAddress(id);
  }, 'Удаляем адрес...');
};

onMounted(async () => {
  await fetchAddresses();
});
</script>

<template>
  <section class="addresses-page page-shell">
    <div class="addresses-page__container container">
      <div class="page-head">
        <div>
          <h1 class="addresses-page__title page-title">
            Мои адреса
          </h1>

          <p class="addresses-page__subtitle page-subtitle">
            Добавьте несколько адресов, чтобы быстрее оформлять заказы.
          </p>
        </div>
      </div>

      <div
          v-if="errorMessage"
          class="addresses-page__error state-message state-message--error"
      >
        {{ errorMessage }}
      </div>

      <div class="addresses-page__layout">
        <!-- список адресов -->
        <div class="addresses-page__list-card surface-card">
          <h2 class="addresses-page__section-title section-title">
            Сохранённые адреса
          </h2>

          <div
              v-if="loading"
              class="addresses-page__loading state-message state-message--loading"
          >
            Загрузка адресов...
          </div>

          <div
              v-else-if="!addresses.length"
              class="addresses-page__empty state-message state-message--empty"
          >
            У вас ещё нет сохранённых адресов.
          </div>

          <ul
              v-else
              class="addresses-page__list"
          >
            <li
                v-for="addr in addresses"
                :key="addr.id"
                class="address-item surface-card--soft"
            >
              <div class="address-item__main">
                <div class="address-item__label-line">
                  <span
                      v-if="addr.label"
                      class="address-item__label"
                  >
                    {{ addr.label }}
                  </span>
                  <span class="address-item__city">
                    {{ addr.city || 'Город не указан' }}
                  </span>
                </div>

                <div class="address-item__line1">
                  {{ addr.line1 }}
                  <span
                      v-if="addr.line2"
                      class="address-item__line2"
                  >
                    , {{ addr.line2 }}
                  </span>
                </div>

                <div
                    v-if="addr.postal_code"
                    class="address-item__postal"
                >
                  {{ addr.postal_code }}
                </div>
              </div>

              <div class="address-item__actions">
                <button
                    type="button"
                    class="button button--ghost button--small"
                    aria-label="Редактировать адрес"
                    @click="startEdit(addr)"
                >
                  <Pencil class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    class="button button--danger button--small"
                    aria-label="Удалить адрес"
                    @click="handleDelete(addr.id)"
                >
                  <Trash2 class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
                </button>
              </div>
            </li>
          </ul>
        </div>

        <!-- форма -->
        <div class="addresses-page__form-card surface-card">
          <h2 class="addresses-page__section-title section-title">
            {{ formMode === 'create' ? 'Новый адрес' : 'Редактирование адреса' }}
          </h2>

          <form class="addresses-form" @submit.prevent="submit">
            <AddressFields v-model="form" required />

            <div class="addresses-form__actions form-actions">
              <button
                  type="submit"
                  class="button"
              >
                {{ formMode === 'create' ? 'Сохранить адрес' : 'Обновить адрес' }}
              </button>

              <button
                  v-if="formMode === 'edit'"
                  type="button"
                  class="button button--ghost"
                  @click="startCreate"
              >
                Отмена
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

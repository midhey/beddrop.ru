<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { useSeoMeta } from '#imports';
import AddressPicker from '~/components/address/AddressPicker.vue';
import { useAddresses, type Address, type AddressPayload } from '~/composables/useAddresses';
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

const emptyAddressForm = (): AddressPayload => ({
  label: '',
  value: null,
  unrestricted_value: null,
  line1: null,
  line2: null,
  city: null,
  postal_code: null,
  lat: null,
  lng: null,
  flat: null,
  entrance: null,
  floor: null,
  intercom: null,
});

const form = ref<AddressPayload>(emptyAddressForm());

const resetForm = () => {
  formMode.value = 'create';
  editingId.value = null;
  form.value = emptyAddressForm();
};

const startCreate = () => {
  resetForm();
};

const startEdit = (addr: Address) => {
  formMode.value = 'edit';
  editingId.value = addr.id;
  form.value = {
    ...addr,
    raw_dadata_json: addr.raw_dadata ?? addr.raw_dadata_json ?? null,
  };
};

const submit = async () => {
  if (!form.value.value || form.value.lat == null || form.value.lng == null) {
    feedback.failure('Выберите адрес из подсказок или на карте');
    return;
  }

  const payload = {
    ...form.value,
    label: form.value.label || null,
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
                    {{ addr.city || addr.settlement || 'Населённый пункт не указан' }}
                  </span>
                </div>

                <div class="address-item__line1">
                  {{ addr.value || addr.line1 }}
                  <span
                      v-if="addr.flat || addr.entrance || addr.floor"
                      class="address-item__line2"
                  >
                    , {{ [
                      addr.entrance ? `подъезд ${addr.entrance}` : null,
                      addr.floor ? `этаж ${addr.floor}` : null,
                      addr.flat ? `кв. ${addr.flat}` : null,
                    ].filter(Boolean).join(', ') }}
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
            <AddressPicker v-model="form" required />

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

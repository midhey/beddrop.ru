<script setup lang="ts">
interface AddressFormValue {
  label: string;
  line1: string;
  line2: string;
  city: string;
  postal_code: string;
}

withDefaults(
  defineProps<{
    line1Label?: string;
    line2Label?: string;
    line1Placeholder?: string;
    line2Placeholder?: string;
    cityPlaceholder?: string;
    postalCodePlaceholder?: string;
    required?: boolean;
  }>(),
  {
    line1Label: 'Адрес',
    line2Label: 'Подъезд, этаж, квартира',
    line1Placeholder: 'Улица, дом, корпус...',
    line2Placeholder: 'Подъезд 3, этаж 8, кв. 42',
    cityPlaceholder: 'Город',
    postalCodePlaceholder: '123456',
    required: false,
  },
);

const model = defineModel<AddressFormValue>({
  required: true,
});
</script>

<template>
  <div class="address-fields">
    <div class="form-field">
      <label class="form-field__label">
        Название (метка)
      </label>
      <input
        v-model="model.label"
        type="text"
        class="form-field__input field-input"
        placeholder="Дом, Работа..."
      >
    </div>

    <div class="form-field">
      <label class="form-field__label">
        {{ line1Label }}
        <span v-if="required" class="address-fields__required">*</span>
      </label>
      <input
        v-model="model.line1"
        type="text"
        class="form-field__input field-input"
        :placeholder="line1Placeholder"
        :required="required"
      >
    </div>

    <div class="form-field">
      <label class="form-field__label">
        {{ line2Label }}
      </label>
      <input
        v-model="model.line2"
        type="text"
        class="form-field__input field-input"
        :placeholder="line2Placeholder"
      >
    </div>

    <div class="address-fields__row">
      <div class="form-field">
        <label class="form-field__label">
          Город
        </label>
        <input
          v-model="model.city"
          type="text"
          class="form-field__input field-input"
          :placeholder="cityPlaceholder"
        >
      </div>

      <div class="form-field">
        <label class="form-field__label">
          Индекс
        </label>
        <input
          v-model="model.postal_code"
          type="text"
          class="form-field__input field-input"
          :placeholder="postalCodePlaceholder"
        >
      </div>
    </div>
  </div>
</template>

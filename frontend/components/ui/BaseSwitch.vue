<script setup lang="ts">
import { computed, useId } from 'vue';

type SwitchValue = boolean | string | number | null | undefined;

const props = withDefaults(defineProps<{
  modelValue: SwitchValue;
  trueValue?: SwitchValue;
  falseValue?: SwitchValue;
  id?: string;
  label?: string;
  description?: string;
  disabled?: boolean;
}>(), {
  trueValue: true,
  falseValue: false,
  disabled: false,
});

const emit = defineEmits<{
  (event: 'update:modelValue', value: SwitchValue): void;
}>();

const fallbackId = useId();
const switchId = computed(() => props.id || `base-switch-${fallbackId}`);
const checked = computed(() => props.modelValue === props.trueValue);

const onChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  emit('update:modelValue', target.checked ? props.trueValue : props.falseValue);
};
</script>

<template>
  <label
    class="base-switch"
    :class="{
      'base-switch--checked': checked,
      'base-switch--disabled': disabled,
    }"
    :for="switchId"
  >
    <input
      :id="switchId"
      class="base-switch__input"
      type="checkbox"
      role="switch"
      :checked="checked"
      :disabled="disabled"
      :aria-checked="checked"
      @change="onChange"
    >
    <span class="base-switch__control" aria-hidden="true">
      <span class="base-switch__thumb" />
    </span>
    <span v-if="$slots.default || label || description" class="base-switch__content">
      <span v-if="$slots.default || label" class="base-switch__label">
        <slot>{{ label }}</slot>
      </span>
      <span v-if="description" class="base-switch__description">
        {{ description }}
      </span>
    </span>
  </label>
</template>

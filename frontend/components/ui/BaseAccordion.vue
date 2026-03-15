<script setup lang="ts">
import { computed, ref, useId } from 'vue';

const props = withDefaults(defineProps<{
  modelValue?: boolean;
  defaultOpen?: boolean;
  disabled?: boolean;
  tag?: string;
}>(), {
  defaultOpen: false,
  disabled: false,
  tag: 'div',
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'open'): void;
  (e: 'close'): void;
}>();

const localOpen = ref(props.defaultOpen);
const panelId = `ui-accordion-panel-${useId()}`;

const isControlled = computed(() => props.modelValue !== undefined);

const isOpen = computed(() => {
  return isControlled.value ? Boolean(props.modelValue) : localOpen.value;
});

const setOpen = (value: boolean) => {
  if (props.disabled || value === isOpen.value) {
    return;
  }

  if (!isControlled.value) {
    localOpen.value = value;
  }

  emit('update:modelValue', value);
  emit(value ? 'open' : 'close');
};

const toggle = () => {
  setOpen(!isOpen.value);
};

const close = () => {
  setOpen(false);
};

const triggerAttrs = computed(() => ({
  type: 'button',
  'aria-expanded': isOpen.value,
  'aria-controls': panelId,
  'aria-disabled': props.disabled,
  'data-accordion-trigger': '',
}));

const panelAttrs = computed(() => ({
  id: panelId,
  'data-accordion-panel': '',
}));

const panelInnerAttrs = computed(() => ({
  'data-accordion-panel-inner': '',
}));
</script>

<template>
  <component
      :is="tag"
      class="ui-accordion"
      :data-accordion-root="''"
      :data-state="isOpen ? 'open' : 'closed'"
  >
    <slot
        :open="isOpen"
        :toggle="toggle"
        :close="close"
        :trigger-attrs="triggerAttrs"
        :panel-attrs="panelAttrs"
        :panel-inner-attrs="panelInnerAttrs"
    />
  </component>
</template>

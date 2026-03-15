<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue';

type DropdownPlacement = 'bottom-start' | 'bottom-end';

const props = withDefaults(defineProps<{
  modelValue?: boolean;
  defaultOpen?: boolean;
  disabled?: boolean;
  hoverable?: boolean;
  closeOnOutside?: boolean;
  closeOnEscape?: boolean;
  placement?: DropdownPlacement;
  tag?: string;
  expandedBelow?: number | null;
}>(), {
  defaultOpen: false,
  disabled: false,
  hoverable: false,
  closeOnOutside: true,
  closeOnEscape: true,
  placement: 'bottom-start',
  tag: 'div',
  expandedBelow: null,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'open'): void;
  (e: 'close'): void;
}>();

const rootRef = ref<HTMLElement | null>(null);
const viewportWidth = ref<number | null>(null);
const localOpen = ref(props.defaultOpen);
const panelId = `ui-dropdown-panel-${useId()}`;

const isControlled = computed(() => props.modelValue !== undefined);

const syncViewport = () => {
  if (typeof window === 'undefined') {
    return;
  }

  viewportWidth.value = window.innerWidth;
};

const isExpandedLayout = computed(() => {
  if (props.expandedBelow === null || viewportWidth.value === null) {
    return false;
  }

  return viewportWidth.value <= props.expandedBelow;
});

const isOpen = computed(() => {
  if (isExpandedLayout.value) {
    return true;
  }

  return isControlled.value ? Boolean(props.modelValue) : localOpen.value;
});

const setOpen = (value: boolean) => {
  if (props.disabled || isExpandedLayout.value || value === isOpen.value) {
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

const handleDocumentClick = (event: MouseEvent) => {
  if (!props.closeOnOutside || isExpandedLayout.value || !isOpen.value) {
    return;
  }

  const target = event.target as Node | null;

  if (target && rootRef.value?.contains(target)) {
    return;
  }

  close();
};

const handleKeydown = (event: KeyboardEvent) => {
  if (!props.closeOnEscape || event.key !== 'Escape' || isExpandedLayout.value || !isOpen.value) {
    return;
  }

  close();
};

const triggerAttrs = computed(() => ({
  type: 'button',
  'aria-expanded': isOpen.value,
  'aria-controls': panelId,
  'aria-disabled': props.disabled || isExpandedLayout.value,
  'data-dropdown-trigger': '',
}));

const panelAttrs = computed(() => ({
  id: panelId,
  'data-dropdown-panel': '',
}));

const panelInnerAttrs = computed(() => ({
  'data-dropdown-panel-inner': '',
}));

onMounted(() => {
  if (typeof window === 'undefined') {
    return;
  }

  syncViewport();
  window.addEventListener('resize', syncViewport, { passive: true });
  document.addEventListener('click', handleDocumentClick);
  document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  if (typeof window === 'undefined') {
    return;
  }

  window.removeEventListener('resize', syncViewport);
  document.removeEventListener('click', handleDocumentClick);
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <component
      :is="tag"
      ref="rootRef"
      class="ui-dropdown"
      :data-dropdown-root="''"
      :data-state="isOpen ? 'open' : 'closed'"
      :data-placement="placement"
      :data-hoverable="hoverable && !isExpandedLayout ? 'true' : 'false'"
      :data-expanded="isExpandedLayout ? 'true' : 'false'"
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

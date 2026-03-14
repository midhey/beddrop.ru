<script setup lang="ts">
import { watch, onBeforeUnmount } from 'vue';
import { X } from 'lucide-vue-next';
import { lockScroll, unlockScroll } from '@/assets/utils/dom';

const props = defineProps<{
  modelValue: boolean;
  title?: string;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
}>();

const close = () => emit('update:modelValue', false);

const onOverlayClick = () => close();

const onKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') close();
};

watch(
    () => props.modelValue,
    (isOpen) => {
      if (isOpen) {
        lockScroll();
      } else {
        unlockScroll();
      }
    },
    { immediate: true }
);

onBeforeUnmount(() => {
  unlockScroll();
});
</script>

<template>
  <Teleport to="body">
    <div
        v-if="modelValue"
        class="modal__overlay"
        @click.self="onOverlayClick"
        @keydown="onKeyDown"
        tabindex="-1"
    >
      <div class="modal">
        <header class="modal__header">
          <h3 v-if="title">{{ title }}</h3>
          <button
              class="modal__close-btn"
              type="button"
              aria-label="Закрыть"
              @click="close"
          >
            <X class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
          </button>
        </header>
        <div class="modal__body">
          <slot />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { useFeedback } from '~/composables/useFeedback';

const props = defineProps<{
  title?: string;
  subtitle?: string;
  loading?: boolean;
  emptyText?: string;
  hasItems?: boolean;
}>();

const feedback = useFeedback();
const gridRef = ref<HTMLElement | null>(null);

watch(
  [() => !!props.loading, gridRef],
  ([isLoading, gridElement]) => {
    if (!gridElement) {
      return;
    }

    if (isLoading) {
      feedback.block(gridElement, 'Загружаем...');
      return;
    }

    feedback.unblock(gridElement);
  },
  {
    immediate: true,
    flush: 'post',
  },
);

onBeforeUnmount(() => {
  feedback.unblock(gridRef.value);
});
</script>

<template>
  <section class="cards">
    <div class="cards__header" v-if="title || subtitle">
      <div class="cards__title-block">
        <h1 v-if="title" class="cards__title">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="cards__subtitle">
          {{ subtitle }}
        </p>
      </div>

      <div class="cards__header-actions">
        <slot name="actions" />
      </div>
    </div>

    <div v-if="$slots['before-content']" class="cards__before-content">
      <slot name="before-content" />
    </div>

    <div
        ref="gridRef"
        class="cards__grid"
        :class="{ 'cards__grid--loading': loading }"
    >
      <slot />
    </div>

    <div
        v-if="!loading && !props.hasItems"
        class="cards__empty"
    >
      <slot name="empty">
        {{ emptyText || 'Ничего не найдено' }}
      </slot>
    </div>
  </section>
</template>

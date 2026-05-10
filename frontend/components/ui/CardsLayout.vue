<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  title?: string;
  subtitle?: string;
  loading?: boolean;
  emptyText?: string;
  hasItems?: boolean;
  skeletonCount?: number;
}>();

const skeletonItems = computed(() => {
  return Array.from({ length: props.skeletonCount ?? 6 }, (_, index) => index);
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
        class="cards__grid"
        :class="{ 'cards__grid--loading': loading }"
    >
      <template v-if="loading">
        <article
            v-for="item in skeletonItems"
            :key="item"
            class="cards__skeleton-card"
            aria-hidden="true"
        >
          <span class="cards__skeleton-image skeleton" />
          <span class="cards__skeleton-line cards__skeleton-line--title skeleton" />
          <span class="cards__skeleton-line skeleton" />
          <span class="cards__skeleton-line cards__skeleton-line--short skeleton" />
          <span class="cards__skeleton-chip skeleton" />
        </article>
      </template>

      <slot v-else />
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

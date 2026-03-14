<script setup lang="ts">
import { computed } from "vue";
import type { Restaurant } from "~/composables/useRestaurants";

import placeholderImg from "~/assets/images/placeholder.png";

const props = defineProps<{
  restaurant: Restaurant;
}>();

const emit = defineEmits<{
  (e: "click", restaurant: Restaurant): void;
}>();

const onClick = () => {
  emit("click", props.restaurant);
};

const addressText = computed(() => {
  const addr = props.restaurant.address;
  if (!addr) return "Адрес не указан";

  const parts = [addr.city, addr.line1, addr.line2].filter(Boolean);
  return parts.join(", ");
});

const prepTimeText = computed(() => {
  const min = props.restaurant.prep_time_min;
  const max = props.restaurant.prep_time_max;

  if (min && max) return `${min}–${max} мин.`;
  if (min) return `от ${min} мин.`;
  if (max) return `до ${max} мин.`;

  return "Время приготовления не указано";
});

// вычисляем, какую картинку показывать
const imageSrc = computed(() => {
  if (props.restaurant.logo?.url) return props.restaurant.logo.url;
  return placeholderImg; // fallback
});
</script>

<template>
  <article class="restaurant-card" @click="onClick">
    <div class="restaurant-card__logo-wrapper">
      <!-- Если есть картинка/заглушка — показываем -->
      <img
        v-if="imageSrc"
        :src="imageSrc"
        :alt="restaurant.name"
        class="restaurant-card__logo"
      />

      <!-- Дополнительный fallback — буква -->
      <div
        v-else
        class="restaurant-card__logo restaurant-card__logo--placeholder"
      >
        <span class="restaurant-card__logo-text">
          {{ restaurant.name.charAt(0).toUpperCase() }}
        </span>
      </div>
    </div>

    <div class="restaurant-card__content">
      <h2 class="restaurant-card__name">
        {{ restaurant.name }}
      </h2>

      <p class="restaurant-card__address">
        {{ addressText }}
      </p>

      <div class="restaurant-card__meta">
        <span class="restaurant-card__meta-item">
          {{ prepTimeText }}
        </span>

        <span v-if="restaurant.phone" class="restaurant-card__meta-item">
          {{ restaurant.phone }}
        </span>
      </div>
    </div>

    <div class="restaurant-card__footer">
      <span
        class="restaurant-card__status"
        :data-status="restaurant.is_active ? 'active' : 'inactive'"
      >
        {{ restaurant.is_active ? "Открыт" : "Закрыт" }}
      </span>
    </div>
  </article>
</template>


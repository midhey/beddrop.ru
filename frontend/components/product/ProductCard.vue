<script setup lang="ts">
import { computed, toRef } from 'vue';
import { Minus, Plus } from 'lucide-vue-next';
import type { Product } from '~/composables/useRestaurantProducts';

import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';

import placeholderImg from '~/assets/images/placeholder.png';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import { useProductCard } from '~/composables/useProductCard';
import { useCartStore } from '~/stores/cart';

const props = defineProps<{
  product: Product;
}>();

const emit = defineEmits<{
  (e: 'change-quantity', payload: { productId: number; quantity: number }): void;
}>();

const productRef = toRef(props, 'product');

const {
  images,
  hasImages,
  hasMultipleImages,
  coverImage,
  priceLabel,
} = useProductCard(productRef);

// --- корзина ---
const cartStore = useCartStore();
const quantity = computed(() => cartStore.getQuantity(props.product.id));

const onIncrement = async () => {
  await cartStore.incrementProduct(props.product, 1);
  emit('change-quantity', {
    productId: props.product.id,
    quantity: quantity.value,
  });
};

const onDecrement = async () => {
  if (quantity.value === 0) return;
  const next = quantity.value - 1;
  await cartStore.setProductQuantity(props.product, next);
  emit('change-quantity', {
    productId: props.product.id,
    quantity: quantity.value,
  });
};

const imageSrc = computed(() => {
  if (coverImage?.value?.media?.url) return coverImage.value.media.url;

  if (images.value.length > 0 && images.value[0]?.media?.url) {
    return images.value[0].media.url;
  }

  return placeholderImg;
});
</script>

<template>
  <article class="product-card">
    <div class="product-card__image-wrapper">
      <ClientOnly>
        <!-- Несколько картинок — слайдер -->
        <Swiper
            v-if="hasMultipleImages"
            :modules="[Pagination, Autoplay]"
            :slides-per-view="1"
            :space-between="8"
            :pagination="{ clickable: true }"
            loop
            :autoplay="{
            delay: 5000,
            disableOnInteraction: false
          }"
            class="product-card__swiper"
        >
          <SwiperSlide
              v-for="img in images"
              :key="img.id"
          >
            <img
                :src="img.media.url"
                :alt="product.name"
                class="product-card__image"
                loading="lazy"
            >
          </SwiperSlide>
        </Swiper>

        <!-- Одна картинка -->
        <img
            v-else-if="coverImage"
            :src="coverImage.media.url"
            :alt="product.name"
            class="product-card__image"
            loading="lazy"
        >

        <div v-else class="product-card__image-wrapper">
          <img
              :src="imageSrc"
              :alt="product.name"
              class="product-card__image product-card__image--placeholder"
              loading="lazy"
          >
        </div>
      </ClientOnly>
    </div>

    <div class="product-card__body">
      <div class="product-card__header">
        <h3 class="product-card__title">
          {{ product.name }}
        </h3>
        <span class="product-card__price">
          {{ priceLabel }}
        </span>
      </div>

      <p
          v-if="product.description"
          class="product-card__description"
      >
        {{ product.description }}
      </p>

      <div class="product-card__footer">
        <span
            v-if="product.category"
            class="product-card__category"
        >
          {{ product.category.name }}
        </span>

        <div class="product-card__qty">
          <button
              type="button"
              class="product-card__qty-btn product-card__qty-btn--minus"
              :disabled="quantity === 0"
              aria-label="Уменьшить количество"
              @click="onDecrement"
          >
            <Minus class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
          </button>
          <span class="product-card__qty-value">
            {{ quantity }}
          </span>
          <button
              type="button"
              class="product-card__qty-btn product-card__qty-btn--plus"
              aria-label="Увеличить количество"
              @click="onIncrement"
          >
            <Plus class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>
  </article>
</template>

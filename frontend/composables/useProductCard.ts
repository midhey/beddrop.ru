import { computed, ref, type Ref } from 'vue';
import type { Product } from '~/composables/useRestaurantProducts';

// импорт заглушки из assets
import placeholderImg from '~/assets/images/placeholder.png';

export function useProductCard(product: Ref<Product>) {
    const images = computed(() => product.value.images || []);

    const hasImages = computed(() => images.value.length > 0);
    const hasMultipleImages = computed(() => images.value.length > 1);

    const coverImage = computed(() => {
        if (!images.value.length) return null;
        const cover = images.value.find((img) => img.is_cover);
        return cover || images.value[0];
    });

    const priceLabel = computed(() => {
        const raw = product.value.price;
        const num = typeof raw === 'string' ? Number(raw) : raw;
        if (Number.isNaN(num)) return raw as any;

        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            maximumFractionDigits: 0,
        }).format(num);
    });

    // локальное количество (позже можно связать с корзиной)
    const quantity = ref(0);

    const setQuantity = (next: number) => {
        if (next < 0) next = 0;
        quantity.value = next;
    };

    const increment = () => setQuantity(quantity.value + 1);
    const decrement = () => setQuantity(quantity.value - 1);

    const placeholderSrc = placeholderImg;

    return {
        images,
        hasImages,
        hasMultipleImages,
        coverImage,
        priceLabel,

        quantity,
        setQuantity,
        increment,
        decrement,

        placeholderSrc,
    };
}
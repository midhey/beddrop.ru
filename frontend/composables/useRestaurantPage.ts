import { ref, computed, watch } from 'vue';
import { useRouter, useNuxtApp } from '#app';
import { useSeoMeta } from '#imports';
import { useRestaurantProducts } from '~/composables/useRestaurantProducts';
import type { Restaurant } from '~/composables/useRestaurants'; // лучше так, вместо локального интерфейса

export function useRestaurantPage(slugRef: Readonly<Ref<string>>) {
    const router = useRouter();
    const { $api } = useNuxtApp();

    const restaurant = ref<Restaurant | null>(null);
    const restaurantLoading = ref(false);
    const restaurantError = ref<string | null>(null);

    const {
        items: products,
        loading: productsLoading,
        errorMessage: productsError,
        fetchProducts,
    } = useRestaurantProducts();

    const loading = computed(
        () => restaurantLoading.value || productsLoading.value,
    );

    const fullAddress = computed(() => {
        if (!restaurant.value?.address) return 'Адрес не указан';

        const { address } = restaurant.value;
        const parts = [
            address.city,
            address.line1,
            address.line2,
            address.postal_code,
        ].filter(Boolean);

        return parts.join(', ');
    });

    const prepTimeText = computed(() => {
        const r = restaurant.value;
        if (!r) return null;

        const min = r.prep_time_min;
        const max = r.prep_time_max;

        if (min && max) return `Время приготовления: ~${min}–${max} мин`;
        if (min && !max) return `Время приготовления: от ${min} мин`;
        if (!min && max) return `Время приготовления: до ${max} мин`;
        return null;
    });

    // категории из продуктов
    type CategoryTab = {
        id: number;
        slug: string;
        name: string;
        sort_order: number;
    };

    const categories = computed<CategoryTab[]>(() => {
        const map = new Map<number, CategoryTab>();

        for (const p of products.value) {
            if (p.category) {
                map.set(p.category.id, {
                    id: p.category.id,
                    slug: p.category.slug,
                    name: p.category.name,
                    sort_order: p.category.sort_order ?? 0,
                });
            }
        }

        return [...map.values()].sort((a, b) => {
            if (a.sort_order === b.sort_order) {
                return a.name.localeCompare(b.name);
            }
            return a.sort_order - b.sort_order;
        });
    });

    const selectedCategorySlug = ref<string>('all');

    const filteredProducts = computed(() => {
        if (selectedCategorySlug.value === 'all') {
            return products.value;
        }

        return products.value.filter(
            (p) => p.category?.slug === selectedCategorySlug.value,
        );
    });

    watch(categories, (cats) => {
        if (
            selectedCategorySlug.value !== 'all' &&
            !cats.some((c) => c.slug === selectedCategorySlug.value)
        ) {
            selectedCategorySlug.value = 'all';
        }
    });

    const loadRestaurant = async () => {
        restaurantLoading.value = true;
        restaurantError.value = null;

        try {
            const { data } = await $api.get<{ restaurant: Restaurant }>(
                `/restaurants/${slugRef.value}`,
            );
            restaurant.value = data.restaurant;
        } catch (e: any) {
            restaurantError.value =
                e?.response?.data?.message || 'Не удалось загрузить ресторан';

            if (e?.response?.status === 404) {
                await router.push('/');
            }
        } finally {
            restaurantLoading.value = false;
        }
    };

    const loadProducts = async () => {
        if (!slugRef.value) return;
        await fetchProducts(slugRef.value);
    };

    const init = async () => {
        await loadRestaurant();
        await loadProducts();
    };

    // SEO прямо здесь, чтобы страница не забивалась
    useSeoMeta(() => ({
        title: restaurant.value
            ? `${restaurant.value.name} — BedDrop`
            : 'Ресторан — BedDrop',
    }));

    watch(slugRef, async () => {
        await init();
    });

    return {
        // state
        restaurant,
        restaurantLoading,
        restaurantError,

        products,
        productsLoading,
        productsError,

        loading,
        categories,
        selectedCategorySlug,
        filteredProducts,

        fullAddress,
        prepTimeText,

        // methods
        init,
    };
}
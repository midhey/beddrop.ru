import { computed, ref, watch, type Ref } from 'vue';
import { useRouter } from '#app';
import { useAsyncData } from '#imports';
import { getRestaurantBySlug } from '~/domains/restaurants/api';
import { listRestaurantProducts } from '~/domains/restaurants/manage/api';
import type { Product } from '~/composables/useRestaurantProducts';
import type { Restaurant } from '~/composables/useRestaurants';
import {
    formatRestaurantWorkingHours,
    getRestaurantAvailabilityLabel,
    getRestaurantAvailabilityStatus,
} from '~/domains/restaurants/presentation';

type RestaurantPageData = {
    slug: string;
    restaurant: Restaurant | null;
    products: Product[];
    productsError: string | null;
};

const emptyRestaurantPageData = (slug: string): RestaurantPageData => ({
    slug,
    restaurant: null,
    products: [],
    productsError: null,
});

const getErrorStatus = (error: any): number | null => {
    const status = error?.response?.status ?? error?.statusCode ?? error?.status;

    return typeof status === 'number' ? status : null;
};

const getErrorMessage = (error: any, fallback: string): string => {
    return error?.response?.data?.message
        || error?.data?.message
        || error?.message
        || fallback;
};

export async function useRestaurantPage(slugRef: Readonly<Ref<string>>) {
    const router = useRouter();
    const selectedCategorySlug = ref<string>('all');

    const {
        data: pageData,
        pending,
        error,
        refresh,
    } = await useAsyncData<RestaurantPageData>(
        'restaurant-page',
        async () => {
            const slug = slugRef.value;

            if (!slug) {
                return emptyRestaurantPageData(slug);
            }

            const [restaurantResult, productsResult] = await Promise.allSettled([
                getRestaurantBySlug(slug),
                listRestaurantProducts(slug, { per_page: 100 }),
            ]);

            if (restaurantResult.status === 'rejected') {
                throw restaurantResult.reason;
            }

            return {
                slug,
                restaurant: restaurantResult.value,
                products: productsResult.status === 'fulfilled'
                    ? productsResult.value.items
                    : [],
                productsError: productsResult.status === 'rejected'
                    ? getErrorMessage(productsResult.reason, 'Не удалось загрузить меню')
                    : null,
            };
        },
        {
            default: () => emptyRestaurantPageData(slugRef.value),
            lazy: true,
            watch: [slugRef],
        },
    );

    const isCurrentData = computed(() => pageData.value?.slug === slugRef.value);

    const restaurant = computed(() => {
        if (!isCurrentData.value) return null;

        return pageData.value?.restaurant ?? null;
    });

    const products = computed(() => {
        if (!isCurrentData.value) return [];

        return pageData.value?.products ?? [];
    });

    const productsError = computed(() => {
        if (!isCurrentData.value) return null;

        return pageData.value?.productsError ?? null;
    });

    const seoTitle = computed(() => {
        return restaurant.value
            ? `${restaurant.value.name} — меню и доставка | BedDrop`
            : 'Ресторан — меню и доставка | BedDrop';
    });

    const seoDescription = computed(() => {
        const currentRestaurant = restaurant.value;

        if (!currentRestaurant) {
            return 'Откройте страницу ресторана на BedDrop, изучите меню и оформите доставку еды онлайн.';
        }

        const descriptionParts = [
            currentRestaurant.description,
            `Меню, цены и доставка из ресторана ${currentRestaurant.name} на BedDrop.`,
            products.value.length ? `${products.value.length} позиций в меню.` : null,
        ].filter(Boolean);

        return descriptionParts.join(' ');
    });

    useAppSeoMeta({
        title: seoTitle,
        description: seoDescription,
        image: computed(() => restaurant.value?.logo?.url || '/images/logo.webp'),
    });

    const loading = computed(() => {
        return pending.value || !isCurrentData.value;
    });

    const restaurantLoading = computed(() => loading.value && !restaurant.value);
    const productsLoading = computed(() => loading.value && products.value.length === 0);

    const restaurantError = computed(() => {
        if (!error.value) return null;

        return getErrorMessage(error.value, 'Не удалось загрузить ресторан');
    });

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

    const workingHoursText = computed(() => formatRestaurantWorkingHours(restaurant.value));
    const availabilityText = computed(() => getRestaurantAvailabilityLabel(restaurant.value));
    const availabilityStatus = computed(() => getRestaurantAvailabilityStatus(restaurant.value));

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

    watch(slugRef, () => {
        selectedCategorySlug.value = 'all';
    });

    const redirectIfNotFound = async (requestError: unknown) => {
        if (getErrorStatus(requestError) === 404) {
            await router.push('/');
        }
    };

    if (import.meta.client) {
        await redirectIfNotFound(error.value);
    }

    watch(error, (requestError) => {
        void redirectIfNotFound(requestError);
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
        workingHoursText,
        availabilityText,
        availabilityStatus,

        // methods
        refresh,
    };
}

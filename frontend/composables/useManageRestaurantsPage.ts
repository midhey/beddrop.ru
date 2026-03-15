import { computed, onMounted } from 'vue';
import { useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useRestaurants } from '~/composables/useRestaurants';
import {
    formatRestaurantAddress,
    formatRestaurantPrepTime,
    getRestaurantActivityLabel,
    getRestaurantActivityStatus,
} from '~/domains/restaurants/presentation';

export function useManageRestaurantsPage() {
    const router = useRouter();
    const { items, loading, errorMessage, fetchMyRestaurants } = useRestaurants();

    useSeoMeta({
        title: 'Мои рестораны — BedDrop',
    });

    const hasRestaurants = computed(() => items.value.length > 0);
    const activeCount = computed(() => items.value.filter((item) => item.is_active).length);
    const inactiveCount = computed(() => Math.max(items.value.length - activeCount.value, 0));

    const loadRestaurants = async () => {
        try {
            await fetchMyRestaurants();
        } catch {
        }
    };

    const goToDashboard = (slug: string) => {
        router.push(`/restaurants/manage/${slug}`);
    };

    const goToPublic = (slug: string) => {
        router.push(`/restaurants/${slug}`);
    };

    const getRestaurantInitial = (name: string) => {
        return name.trim().charAt(0).toUpperCase() || 'R';
    };

    const formatCreatedDate = (value: string) => {
        const parsed = new Date(value);

        if (Number.isNaN(parsed.getTime())) {
            return null;
        }

        return new Intl.DateTimeFormat('ru-RU', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(parsed);
    };

    onMounted(loadRestaurants);

    return {
        items,
        loading,
        errorMessage,
        hasRestaurants,
        activeCount,
        inactiveCount,
        goToDashboard,
        goToPublic,
        getRestaurantInitial,
        formatCreatedDate,
        formatRestaurantAddress,
        formatRestaurantPrepTime,
        getRestaurantActivityLabel,
        getRestaurantActivityStatus,
    };
}

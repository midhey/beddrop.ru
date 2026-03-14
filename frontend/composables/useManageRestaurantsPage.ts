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

    onMounted(loadRestaurants);

    return {
        items,
        loading,
        errorMessage,
        hasRestaurants,
        goToDashboard,
        goToPublic,
        formatRestaurantAddress,
        formatRestaurantPrepTime,
        getRestaurantActivityLabel,
        getRestaurantActivityStatus,
    };
}

import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';

export interface ProductCategory {
    id: number;
    slug: string;
    name: string;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface ProductCategoryPayload {
    slug?: string;
    name: string;
    sort_order?: number;
}

export function useProductCategories() {
    const { $api } = useNuxtApp();
    const { handleApiError, errorMessage } = useApiHelpers();

    const items = ref<ProductCategory[]>([]);
    const loading = ref(false);

    const fetchCategories = async () => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const { data } = await $api.get<{ categories: ProductCategory[] }>(
                '/product-categories',
            );
            items.value = data.categories;
            return data.categories;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const createCategory = async (
        payload: ProductCategoryPayload,
    ): Promise<ProductCategory> => {
        errorMessage.value = null;

        try {
            const { data } = await $api.post<{ category: ProductCategory }>(
                '/product-categories',
                payload,
            );
            return data.category;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const updateCategory = async (
        id: number,
        payload: Partial<ProductCategoryPayload>,
    ): Promise<ProductCategory> => {
        errorMessage.value = null;

        try {
            const { data } = await $api.put<{ category: ProductCategory }>(
                `/product-categories/${id}`,
                payload,
            );
            return data.category;
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const deleteCategory = async (id: number): Promise<void> => {
        errorMessage.value = null;

        try {
            await $api.delete(`/product-categories/${id}`);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    return {
        items,
        loading,
        errorMessage,
        fetchCategories,
        createCategory,
        updateCategory,
        deleteCategory,
    };
}
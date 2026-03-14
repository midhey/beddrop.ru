import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';
import {
    addRestaurantProductImage,
    createRestaurantProduct,
    deleteRestaurantProduct,
    deleteRestaurantProductImage,
    getRestaurantProduct,
    listRestaurantProducts,
    updateRestaurantProduct,
    updateRestaurantProductImage,
} from '~/domains/restaurants/manage/api';
import type { PaginationMeta } from '~/utils/api/pagination';

export interface ProductCategoryRef {
    id: number;
    slug: string;
    name: string;
    sort_order: number;
}

export interface MediaRef {
    id: number;
    url: string;
    mime: string | null;
    size_bytes: number | null;
}

export interface ProductImageRef {
    id: number;
    sort_order: number;
    is_cover: boolean;
    media: MediaRef;
}

export interface Product {
    id: number;
    restaurant_id: number;
    category_id: number;
    name: string;
    description: string | null;
    price: string; // decimal:2
    is_active: boolean;
    created_at: string;
    updated_at: string;

    category?: ProductCategoryRef;
    images?: ProductImageRef[];
}

export interface ProductPayload {
    category_id: number;
    name: string;
    description?: string | null;
    price: number | string;
    is_active?: boolean;
}

export function useRestaurantProducts() {
    const { handleApiError, errorMessage } = useApiHelpers();

    const items = ref<Product[]>([]);
    const pagination = ref<PaginationMeta | null>(null);
    const loading = ref(false);

    const fetchProducts = async (
        restaurantSlug: string,
        params: Record<string, any> = {},
    ) => {
        loading.value = true;
        errorMessage.value = null;

        try {
            const response = await listRestaurantProducts(restaurantSlug, params);
            items.value = response.items;
            pagination.value = response.pagination;
            return response;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const fetchProduct = async (
        restaurantSlug: string,
        productId: number,
    ): Promise<Product> => {
        errorMessage.value = null;

        try {
            return await getRestaurantProduct(restaurantSlug, productId);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const createProduct = async (
        restaurantSlug: string,
        payload: ProductPayload,
    ): Promise<Product> => {
        errorMessage.value = null;

        try {
            return await createRestaurantProduct(restaurantSlug, payload);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const updateProduct = async (
        restaurantSlug: string,
        productId: number,
        payload: Partial<ProductPayload>,
    ): Promise<Product> => {
        errorMessage.value = null;

        try {
            return await updateRestaurantProduct(
                restaurantSlug,
                productId,
                payload,
            );
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const deleteProduct = async (restaurantSlug: string, productId: number) => {
        errorMessage.value = null;

        try {
            await deleteRestaurantProduct(restaurantSlug, productId);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    // --- Работа с картинками продукта --- //

    interface ProductImagePayload {
        media_id: number;
        sort_order?: number;
        is_cover?: boolean;
    }

    const addProductImage = async (
        restaurantSlug: string,
        productId: number,
        payload: ProductImagePayload,
    ): Promise<ProductImageRef> => {
        errorMessage.value = null;

        try {
            return await addRestaurantProductImage(
                restaurantSlug,
                productId,
                payload,
            );
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const updateProductImage = async (
        restaurantSlug: string,
        productId: number,
        imageId: number,
        payload: Partial<ProductImagePayload>,
    ): Promise<ProductImageRef> => {
        errorMessage.value = null;

        try {
            return await updateRestaurantProductImage(
                restaurantSlug,
                productId,
                imageId,
                payload,
            );
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    const deleteProductImage = async (
        restaurantSlug: string,
        productId: number,
        imageId: number,
    ): Promise<void> => {
        errorMessage.value = null;

        try {
            await deleteRestaurantProductImage(
                restaurantSlug,
                productId,
                imageId,
            );
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    return {
        // state
        items,
        pagination,
        loading,
        errorMessage,

        // methods
        fetchProducts,
        fetchProduct,
        createProduct,
        updateProduct,
        deleteProduct,

        // images
        addProductImage,
        updateProductImage,
        deleteProductImage,
    };
}

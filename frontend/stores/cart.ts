import { defineStore } from 'pinia';
import { useNuxtApp } from '#app';
import { useFeedback } from '~/composables/useFeedback';
import type { Product } from '~/composables/useRestaurantProducts';
import type { Restaurant } from '~/composables/useRestaurants';

export interface CartItem {
    id: number;
    product_id: number;
    quantity: number;
    unit_price_snapshot: string;
    subtotal: number;
    product: Product;
}

export interface Cart {
    id: number;
    user_id: number;
    restaurant_id: number | null;
    status: 'ACTIVE' | 'ORDERED' | 'ABANDONED';
    is_active: boolean;
    items: CartItem[];

    restaurant?: Restaurant | null;
    items_count?: number;
    total_price?: string | number;
    created_at?: string;
    updated_at?: string;
}

interface CartState {
    cart: Cart | null;
    loading: boolean;
    error: string | null;
}

export const useCartStore = defineStore('cart', {
    state: (): CartState => ({
        cart: null,
        loading: false,
        error: null,
    }),

    getters: {
        items: (state): CartItem[] => state.cart?.items ?? [],

        totalCount: (state): number =>
            (state.cart?.items ?? []).reduce((sum, item) => sum + item.quantity, 0),

        totalPrice: (state): number => {
            if (!state.cart) return 0;
            if (state.cart.total_price != null) {
                const num = Number(state.cart.total_price);
                return Number.isNaN(num) ? 0 : num;
            }

            // fallback — считаем по items
            return (state.cart.items ?? []).reduce((sum, item) => {
                const price = Number(item.unit_price_snapshot);
                if (Number.isNaN(price)) return sum;
                return sum + price * item.quantity;
            }, 0);
        },

        restaurant: (state): Restaurant | null =>
            (state.cart?.restaurant as Restaurant | null) ?? null,

        getQuantity: (state) => (productId: number): number => {
            const found = state.cart?.items?.find((i) => i.product_id === productId);
            return found ? found.quantity : 0;
        },
    },

    actions: {
        setCart(cart: Cart | null) {
            this.cart = cart;
        },

        setError(message: string | null) {
            this.error = message;
        },

        async fetchCart() {
            const { $api } = useNuxtApp();
            const feedback = useFeedback();
            this.loading = true;
            this.error = null;

            try {
                const { data } = await $api.get<{ cart: Cart | null }>('/cart');
                this.cart = data.cart;
            } catch (e: any) {
                const status = e?.response?.status;
                const msg =
                    e?.response?.data?.message || 'Не удалось загрузить корзину';

                if (status === 401) {
                    this.cart = null;
                    this.error = null;
                    return;
                }

                this.error = msg;
                feedback.failure(msg);
                throw e;
            } finally {
                this.loading = false;
            }
        },

        async incrementProduct(product: Product, delta = 1) {
            const { $api } = useNuxtApp();
            const feedback = useFeedback();
            this.error = null;

            try {
                const { data } = await $api.post<{ cart: Cart }>(
                    '/cart/items',
                    {
                        product_id: product.id,
                        quantity: delta,
                    },
                );
                this.cart = data.cart;
            } catch (e: any) {
                const status = e?.response?.status;
                const msg =
                    e?.response?.data?.message ||
                    'Не удалось добавить товар в корзину';

                if (status === 401) {
                    feedback.info('Авторизуйтесь, чтобы добавить товары в корзину');
                    this.error = null;
                    return;
                }

                this.error = msg;
                feedback.failure(msg);
                throw e;
            }
        },

        async setProductQuantity(product: Product, quantity: number) {
            const { $api } = useNuxtApp();
            const feedback = useFeedback();
            this.error = null;

            const item = this.cart?.items?.find((i) => i.product_id === product.id);
            if (!item) {
                return;
            }

            try {
                const { data } = await $api.put<{ cart: Cart }>(
                    `/cart/items/${item.id}`,
                    { quantity },
                );
                this.cart = data.cart;
            } catch (e: any) {
                const status = e?.response?.status;
                const msg =
                    e?.response?.data?.message ||
                    'Не удалось обновить количество товара';

                if (status === 401) {
                    feedback.info('Авторизуйтесь, чтобы управлять корзиной');
                    this.error = null;
                    return;
                }

                this.error = msg;
                feedback.failure(msg);
                throw e;
            }
        },

        async removeProduct(productId: number) {
            const { $api } = useNuxtApp();
            const feedback = useFeedback();
            this.error = null;

            const item = this.cart?.items?.find((i) => i.product_id === productId);
            if (!item) return;

            try {
                const { data } = await $api.delete<{ cart: Cart }>(
                    `/cart/items/${item.id}`,
                );
                this.cart = data.cart;
            } catch (e: any) {
                const status = e?.response?.status;
                const msg =
                    e?.response?.data?.message ||
                    'Не удалось удалить товар из корзины';

                if (status === 401) {
                    this.error = null;
                    return;
                }

                this.error = msg;
                feedback.failure(msg);
                throw e;
            }
        },

        async clearCart() {
            const { $api } = useNuxtApp();
            const feedback = useFeedback();
            this.error = null;

            try {
                await $api.delete('/cart');
                this.cart = null;
            } catch (e: any) {
                const status = e?.response?.status;
                const msg =
                    e?.response?.data?.message ||
                    'Не удалось очистить корзину';

                if (status === 401) {
                    this.error = null;
                    this.cart = null;
                    return;
                }

                this.error = msg;
                feedback.failure(msg);
                throw e;
            }
        },
    },
});

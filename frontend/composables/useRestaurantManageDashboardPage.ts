import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useFeedback } from '~/composables/useFeedback';
import { useRestaurants, type Restaurant } from '~/composables/useRestaurants';
import { useProductCategories } from '~/composables/useProductCategories';
import { useMediaUpload } from '~/composables/useMediaUpload';
import {
    useRestaurantProducts,
    type Product,
    type ProductPayload,
} from '~/composables/useRestaurantProducts';
import { useRestaurantOrders } from '~/composables/useRestaurantOrders';
import {
    useRestaurantStaff,
    type RestaurantStaffInvite,
    type RestaurantRole,
    type RestaurantStaffMember,
} from '~/composables/useRestaurantStaff';
import type { Order } from '~/composables/useOrders';
import {
    canRestaurantAcceptOrder,
    canRestaurantCancelOrder,
    getOrderStatusClass,
    getOrderStatusLabel,
    getPaymentMethodLabel,
    getPaymentStatusLabel,
} from '~/domains/orders/presentation';
import {
    formatRestaurantAddress,
    formatRestaurantPrepTime,
    getRestaurantActivityLabel,
    getRestaurantActivityStatus,
} from '~/domains/restaurants/presentation';
import { formatDateTime, formatPrice } from '~/utils/formatting';

type TabKey = 'orders' | 'menu' | 'staff';

export function useRestaurantManageDashboardPage() {
    const route = useRoute();
    const router = useRouter();
    const feedback = useFeedback();

    const slug = computed(() => route.params.slug as string);

    const {
        fetchRestaurant,
        errorMessage: restaurantError,
        loading: restaurantLoading,
    } = useRestaurants();
    const restaurant = ref<Restaurant | null>(null);

    const {
        items: products,
        loading: productsLoading,
        errorMessage: productsError,
        fetchProducts,
        createProduct,
        updateProduct,
        deleteProduct,
        addProductImage,
    } = useRestaurantProducts();

    const {
        items: productCategories,
        loading: categoriesLoading,
        fetchCategories,
    } = useProductCategories();

    const { uploadMedia, uploading: imageUploading } = useMediaUpload();

    const {
        items: restaurantOrders,
        loading: ordersLoading,
        errorMessage: ordersError,
        fetchOrders: fetchRestaurantOrders,
        acceptOrder,
        cancelOrder,
    } = useRestaurantOrders();

    const {
        items: staff,
        loading: staffLoading,
        errorMessage: staffError,
        fetchStaff,
        createInvite,
        updateStaff,
        removeStaff,
        invitesLoading,
    } = useRestaurantStaff();

    const activeTab = ref<TabKey>('orders');
    const currentUserRole = computed(() => restaurant.value?.current_user_role ?? null);
    const canViewOrdersTab = computed(() => ['OWNER', 'MANAGER', 'STAFF'].includes(currentUserRole.value ?? ''));
    const canViewMenuTab = computed(() => ['OWNER', 'MANAGER'].includes(currentUserRole.value ?? ''));
    const canViewStaffTab = computed(() => currentUserRole.value === 'OWNER');
    const availableTabs = computed<TabKey[]>(() => {
        return ([
            canViewOrdersTab.value ? 'orders' : null,
            canViewMenuTab.value ? 'menu' : null,
            canViewStaffTab.value ? 'staff' : null,
        ].filter(Boolean) as TabKey[]);
    });

    const baseLoading = computed(
        () =>
            restaurantLoading.value ||
            productsLoading.value ||
            staffLoading.value,
    );

    const errorMessage = computed(
        () =>
            restaurantError.value ||
            productsError.value ||
            ordersError.value ||
            staffError.value,
    );

    useSeoMeta(() => ({
        title: restaurant.value
            ? `Дашборд — ${restaurant.value.name}`
            : 'Ресторан — дашборд',
    }));

    const fullAddress = computed(() => formatRestaurantAddress(restaurant.value));
    const prepTimeText = computed(() => formatRestaurantPrepTime(restaurant.value));

    const hasProducts = computed(() => products.value.length > 0);
    const hasOrders = computed(() => restaurantOrders.value.length > 0);
    const activeProductsCount = computed(() => products.value.filter((product) => product.is_active).length);
    const hiddenProductsCount = computed(() => products.value.filter((product) => !product.is_active).length);
    const menuProducts = computed(() => {
        return [...products.value].sort((left, right) => {
            if (left.is_active !== right.is_active) {
                return Number(right.is_active) - Number(left.is_active);
            }

            return right.id - left.id;
        });
    });

    const actionOrderId = ref<number | null>(null);

    const handleAccept = async (order: Order) => {
        if (!canRestaurantAcceptOrder(order) || actionOrderId.value) return;

        actionOrderId.value = order.id;
        try {
            await acceptOrder(slug.value, order.id);
            feedback.success('Заказ принят');
        } catch {
        } finally {
            actionOrderId.value = null;
        }
    };

    const handleCancel = async (order: Order) => {
        if (!canRestaurantCancelOrder(order) || actionOrderId.value) return;

        actionOrderId.value = order.id;
        try {
            await cancelOrder(slug.value, order.id);
            feedback.success('Заказ отменён рестораном');
        } catch {
        } finally {
            actionOrderId.value = null;
        }
    };

    const showCreateProductForm = ref(false);

    const createForm = ref<{
        name: string;
        price: string;
        category_id: string;
        description: string;
        is_active: boolean;
    }>({
        name: '',
        price: '',
        category_id: '',
        description: '',
        is_active: true,
    });
    const createProductImageFile = ref<File | null>(null);
    const createProductImagePreview = ref<string | null>(null);

    const creatingProduct = ref(false);
    const productActionId = ref<number | null>(null);

    const resetCreateForm = () => {
        createForm.value = {
            name: '',
            price: '',
            category_id: '',
            description: '',
            is_active: true,
        };
        createProductImageFile.value = null;

        if (createProductImagePreview.value) {
            URL.revokeObjectURL(createProductImagePreview.value);
        }

        createProductImagePreview.value = null;
    };

    const handleCreateProductImageChange = (event: Event) => {
        const input = event.target as HTMLInputElement | null;
        const file = input?.files?.[0] ?? null;

        createProductImageFile.value = file;

        if (createProductImagePreview.value) {
            URL.revokeObjectURL(createProductImagePreview.value);
            createProductImagePreview.value = null;
        }

        if (file) {
            createProductImagePreview.value = URL.createObjectURL(file);
        }
    };

    const handleCreateProduct = async () => {
        if (creatingProduct.value) return;

        const name = createForm.value.name.trim();
        const priceNum = Number(createForm.value.price);
        const catIdNum = Number(createForm.value.category_id) || 0;

        if (!name || Number.isNaN(priceNum) || priceNum <= 0 || !catIdNum) {
            feedback.failure('Заполните название, цену и ID категории');
            return;
        }

        creatingProduct.value = true;

        const payload: ProductPayload = {
            name,
            price: priceNum,
            category_id: catIdNum,
            description: createForm.value.description.trim() || null,
            is_active: createForm.value.is_active,
        };

        try {
            const newProduct = await createProduct(slug.value, payload);

            let nextProduct = newProduct;

            if (createProductImageFile.value) {
                const media = await uploadMedia(createProductImageFile.value);
                const image = await addProductImage(slug.value, newProduct.id, {
                    media_id: media.id,
                    is_cover: true,
                    sort_order: 0,
                });

                nextProduct = {
                    ...newProduct,
                    images: [image],
                };
            }

            products.value = [nextProduct, ...products.value];
            feedback.success('Блюдо добавлено');
            resetCreateForm();
            showCreateProductForm.value = false;
        } catch {
        } finally {
            creatingProduct.value = false;
        }
    };

    const toggleProductActive = async (product: Product) => {
        if (productActionId.value) return;

        productActionId.value = product.id;
        const nextActive = !product.is_active;

        products.value = products.value.map((item) =>
            item.id === product.id
                ? {
                    ...item,
                    is_active: nextActive,
                }
                : item,
        );

        try {
            const updated = await updateProduct(slug.value, product.id, {
                is_active: nextActive,
            });

            products.value = products.value.map((item) =>
                item.id === updated.id
                    ? {
                        ...item,
                        ...updated,
                        category: updated.category ?? item.category,
                        images: updated.images?.length ? updated.images : item.images,
                    }
                    : item,
            );
        } catch {
            products.value = products.value.map((item) =>
                item.id === product.id
                    ? {
                        ...item,
                        is_active: product.is_active,
                    }
                    : item,
            );
        } finally {
            productActionId.value = null;
        }
    };

    const handleDeleteProduct = async (product: Product) => {
        if (productActionId.value) return;

        const confirmed = await feedback.confirm({
            title: 'Удалить блюдо',
            message: `Удалить блюдо "${product.name}"?`,
            confirmText: 'Удалить',
            cancelText: 'Отмена',
        });

        if (!confirmed) {
            return;
        }

        productActionId.value = product.id;
        try {
            await deleteProduct(slug.value, product.id);
            products.value = products.value.filter((item) => item.id !== product.id);
            feedback.success('Блюдо удалено');
        } catch {
        } finally {
            productActionId.value = null;
        }
    };

    const inviteRole = ref<Exclude<RestaurantRole, 'OWNER'>>('STAFF');
    const inviteExpiryMinutes = ref<'5' | '15' | '30' | '60'>('5');
    const latestStaffInvite = ref<RestaurantStaffInvite | null>(null);
    const staffActionUserId = ref<number | null>(null);

    const handleCreateStaffInvite = async () => {
        try {
            const invite = await createInvite(slug.value, {
                role: inviteRole.value,
                expires_in_minutes: Number(inviteExpiryMinutes.value),
            });

            latestStaffInvite.value = invite;
            feedback.success('Ссылка-приглашение создана');
        } catch {
        }
    };

    const handleChangeStaffRole = async (member: RestaurantStaffMember) => {
        if (staffActionUserId.value) return;

        staffActionUserId.value = member.id;
        try {
            await updateStaff(slug.value, member.id, {
                role: member.role,
            });
            feedback.success('Роль обновлена');
        } catch {
        } finally {
            staffActionUserId.value = null;
        }
    };

    const handleRemoveStaff = async (member: RestaurantStaffMember) => {
        if (staffActionUserId.value) return;

        const confirmed = await feedback.confirm({
            title: 'Удалить сотрудника',
            message: `Удалить сотрудника ${member.email || member.id}?`,
            confirmText: 'Удалить',
            cancelText: 'Отмена',
        });

        if (!confirmed) {
            return;
        }

        staffActionUserId.value = member.id;
        try {
            await removeStaff(slug.value, member.id);
            feedback.success('Сотрудник удалён');
        } catch {
        } finally {
            staffActionUserId.value = null;
        }
    };

    const init = async () => {
        if (!slug.value) {
            await router.push('/');
            return;
        }

        try {
            restaurant.value = await fetchRestaurant(slug.value);
        } catch (error: any) {
            if (error?.response?.status === 404) {
                await router.push('/restaurants/manage');
                return;
            }
        }

        if (canViewMenuTab.value) {
            try {
                await fetchProducts(slug.value);
            } catch {
            }

            try {
                await fetchCategories();
            } catch {
            }
        }

        if (canViewOrdersTab.value) {
            try {
                await fetchRestaurantOrders(slug.value);
            } catch {
            }
        }

        if (canViewStaffTab.value) {
            try {
                await fetchStaff(slug.value);
            } catch {
            }
        }

        if (availableTabs.value.length && !availableTabs.value.includes(activeTab.value)) {
            activeTab.value = availableTabs.value[0];
        }
    };

    onMounted(init);

    return {
        restaurant,
        activeTab,
        currentUserRole,
        canViewOrdersTab,
        canViewMenuTab,
        canViewStaffTab,
        availableTabs,
        baseLoading,
        errorMessage,
        fullAddress,
        prepTimeText,
        hasProducts,
        hasOrders,
        products,
        menuProducts,
        productCategories,
        categoriesLoading,
        activeProductsCount,
        hiddenProductsCount,
        restaurantOrders,
        staff,
        ordersLoading,
        showCreateProductForm,
        createForm,
        createProductImagePreview,
        imageUploading,
        creatingProduct,
        productActionId,
        inviteRole,
        inviteExpiryMinutes,
        latestStaffInvite,
        staffActionUserId,
        actionOrderId,
        invitesLoading,
        handleAccept,
        handleCancel,
        handleCreateProduct,
        handleCreateProductImageChange,
        toggleProductActive,
        handleDeleteProduct,
        handleCreateStaffInvite,
        handleChangeStaffRole,
        handleRemoveStaff,
        formatPrice,
        formatDateTime,
        getOrderStatusClass,
        getOrderStatusLabel,
        getPaymentMethodLabel,
        getPaymentStatusLabel,
        canRestaurantAcceptOrder,
        canRestaurantCancelOrder,
        getRestaurantActivityLabel,
        getRestaurantActivityStatus,
    };
}

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

type TabKey = 'orders' | 'menu' | 'staff' | 'settings';

export function useRestaurantManageDashboardPage() {
    const route = useRoute();
    const router = useRouter();
    const feedback = useFeedback();

    const slug = computed(() => route.params.slug as string);

    const {
        fetchRestaurant,
        updateRestaurant,
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
    const canViewSettingsTab = computed(() => ['OWNER', 'MANAGER'].includes(currentUserRole.value ?? ''));
    const availableTabs = computed<TabKey[]>(() => {
        return ([
            canViewOrdersTab.value ? 'orders' : null,
            canViewMenuTab.value ? 'menu' : null,
            canViewSettingsTab.value ? 'settings' : null,
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
    const hasRestaurantLogo = computed(() => Boolean(settingsForm.value.logo_preview_url || restaurant.value?.logo?.url));

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
    const savingSettings = ref(false);
    const settingsUploadInputKey = ref(0);
    const settingsLogoFile = ref<File | null>(null);
    const settingsForm = ref({
        name: '',
        description: '',
        phone: '',
        prep_time_min: '',
        prep_time_max: '',
        is_active: true,
        logo_media_id: null as number | null,
        logo_preview_url: '',
        address: {
            label: 'Ресторан',
            line1: '',
            line2: '',
            city: '',
            postal_code: '',
        },
    });

    const syncSettingsForm = (source: Restaurant | null) => {
        settingsLogoFile.value = null;
        settingsUploadInputKey.value += 1;
        settingsForm.value = {
            name: source?.name ?? '',
            description: source?.description ?? '',
            phone: source?.phone ?? '',
            prep_time_min: source?.prep_time_min != null ? String(source.prep_time_min) : '',
            prep_time_max: source?.prep_time_max != null ? String(source.prep_time_max) : '',
            is_active: source?.is_active ?? true,
            logo_media_id: source?.logo_media_id ?? null,
            logo_preview_url: source?.logo?.url ?? '',
            address: {
                label: source?.address?.label ?? 'Ресторан',
                line1: source?.address?.line1 ?? '',
                line2: source?.address?.line2 ?? '',
                city: source?.address?.city ?? '',
                postal_code: source?.address?.postal_code ?? '',
            },
        };
    };

    const handleSettingsLogoChange = (event: Event) => {
        const input = event.target as HTMLInputElement | null;
        const file = input?.files?.[0] ?? null;

        settingsLogoFile.value = file;

        if (file) {
            settingsForm.value.logo_preview_url = URL.createObjectURL(file);
        } else {
            settingsForm.value.logo_preview_url = restaurant.value?.logo?.url ?? '';
        }
    };

    const handleSaveSettings = async () => {
        if (!restaurant.value || savingSettings.value) return;

        const name = settingsForm.value.name.trim();
        const addressLine1 = settingsForm.value.address.line1.trim();
        const prepTimeMin = settingsForm.value.prep_time_min.trim();
        const prepTimeMax = settingsForm.value.prep_time_max.trim();

        if (!name || !addressLine1) {
            feedback.failure('Заполните название ресторана и основной адрес');
            return;
        }

        if ((prepTimeMin && Number(prepTimeMin) < 0) || (prepTimeMax && Number(prepTimeMax) < 0)) {
            feedback.failure('Время приготовления не может быть отрицательным');
            return;
        }

        savingSettings.value = true;

        try {
            let logoMediaId = settingsForm.value.logo_media_id;

            if (settingsLogoFile.value) {
                const media = await uploadMedia(settingsLogoFile.value);
                logoMediaId = media.id;
                settingsForm.value.logo_preview_url = media.url;
            }

            const updatedRestaurant = await updateRestaurant(restaurant.value.id, {
                name,
                description: settingsForm.value.description.trim() || null,
                phone: settingsForm.value.phone.trim() || null,
                is_active: settingsForm.value.is_active,
                prep_time_min: prepTimeMin ? Number(prepTimeMin) : null,
                prep_time_max: prepTimeMax ? Number(prepTimeMax) : null,
                logo_media_id: logoMediaId,
                address: {
                    label: settingsForm.value.address.label.trim() || null,
                    line1: addressLine1,
                    line2: settingsForm.value.address.line2.trim() || null,
                    city: settingsForm.value.address.city.trim() || null,
                    postal_code: settingsForm.value.address.postal_code.trim() || null,
                    lat: restaurant.value.address?.lat ? Number(restaurant.value.address.lat) : null,
                    lng: restaurant.value.address?.lng ? Number(restaurant.value.address.lng) : null,
                },
            });

            restaurant.value = updatedRestaurant;
            syncSettingsForm(updatedRestaurant);
            feedback.success('Настройки ресторана сохранены');
        } catch {
        } finally {
            savingSettings.value = false;
        }
    };

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
            syncSettingsForm(restaurant.value);
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
        canViewSettingsTab,
        availableTabs,
        baseLoading,
        errorMessage,
        fullAddress,
        prepTimeText,
        hasRestaurantLogo,
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
        settingsForm,
        settingsUploadInputKey,
        savingSettings,
        handleAccept,
        handleCancel,
        handleSettingsLogoChange,
        handleSaveSettings,
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

import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from '#app';
import { useFeedback } from '~/composables/useFeedback';
import type { AddressPayload } from '~/composables/useAddresses';
import { useRestaurants, type Restaurant } from '~/composables/useRestaurants';
import { useProductCategories } from '~/composables/useProductCategories';
import { useMediaUpload } from '~/composables/useMediaUpload';
import {
    useRestaurantProducts,
    type Product,
    type ProductImageRef,
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
    canRestaurantMarkReadyOrder,
    canRestaurantCancelOrder,
    getOrderStatusClass,
    getOrderStatusLabel,
    getRestaurantOrderNextStep,
    getPaymentMethodLabel,
    getPaymentStatusLabel,
} from '~/domains/orders/presentation';
import {
    formatRestaurantAddress,
    formatRestaurantPrepTime,
    formatRestaurantWorkingHours,
    getRestaurantActivityLabel,
    getRestaurantActivityStatus,
    getRestaurantAvailabilityLabel,
    getRestaurantAvailabilityStatus,
} from '~/domains/restaurants/presentation';
import { formatDateTime, formatPrice } from '~/utils/formatting';

type TabKey = 'orders' | 'menu' | 'staff' | 'settings';
type ProductFormState = {
    name: string;
    price: string;
    category_id: string;
    description: string;
    is_active: boolean;
};

const PRODUCT_IMAGE_LIMIT = 5;
const RESTAURANT_TIMEZONES = [
    'Europe/Moscow',
    'Europe/Samara',
    'Asia/Yekaterinburg',
    'Asia/Novosibirsk',
    'Asia/Krasnoyarsk',
    'Asia/Irkutsk',
    'Asia/Yakutsk',
    'Asia/Vladivostok',
];

const emptyProductForm = (): ProductFormState => ({
    name: '',
    price: '',
    category_id: '',
    description: '',
    is_active: true,
});

const revokeObjectUrls = (urls: string[]) => {
    for (const url of urls) {
        URL.revokeObjectURL(url);
    }
};

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
        updateProductImage,
        deleteProductImage,
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
        markReady,
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

    useAppSeoMeta({
        title: computed(() => restaurant.value
            ? `Дашборд — ${restaurant.value.name}`
            : 'Ресторан — дашборд'),
        description: computed(() => restaurant.value
            ? `Управление рестораном ${restaurant.value.name}: заказы, меню, сотрудники и настройки доступности.`
            : 'Управление рестораном BedDrop: заказы, меню, сотрудники и настройки доступности.'),
        robots: 'noindex,nofollow',
    });

    const fullAddress = computed(() => formatRestaurantAddress(restaurant.value));
    const prepTimeText = computed(() => formatRestaurantPrepTime(restaurant.value));
    const workingHoursText = computed(() => formatRestaurantWorkingHours(restaurant.value));
    const availabilityText = computed(() => getRestaurantAvailabilityLabel(restaurant.value));
    const availabilityStatus = computed(() => getRestaurantAvailabilityStatus(restaurant.value));
    const prepTimeFieldValue = (value: string | number | null | undefined): string => {
        return value == null ? '' : String(value).trim();
    };
    const settingsPrepAverageText = computed(() => {
        const minValue = prepTimeFieldValue(settingsForm.value.prep_time_min);
        const maxValue = prepTimeFieldValue(settingsForm.value.prep_time_max);
        const min = Number(minValue);
        const max = Number(maxValue);
        const hasMin = minValue !== '' && !Number.isNaN(min);
        const hasMax = maxValue !== '' && !Number.isNaN(max);

        if (hasMin && hasMax) {
            return `${Math.ceil((min + max) / 2)} мин`;
        }

        if (hasMin) {
            return `${min} мин`;
        }

        if (hasMax) {
            return `${max} мин`;
        }

        return 'будет взято из глобальных настроек';
    });
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
        accepts_orders: true,
        timezone: 'Europe/Moscow',
        opens_at: '',
        closes_at: '',
        closed_reason: '',
        logo_media_id: null as number | null,
        logo_preview_url: '',
        address: {
            label: 'Ресторан',
            value: null,
            unrestricted_value: null,
            line1: null,
            line2: null,
            city: null,
            postal_code: null,
            lat: null,
            lng: null,
            flat: null,
            entrance: null,
            floor: null,
            intercom: null,
        } as AddressPayload,
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
            accepts_orders: source?.accepts_orders ?? true,
            timezone: source?.timezone ?? 'Europe/Moscow',
            opens_at: source?.opens_at ?? '',
            closes_at: source?.closes_at ?? '',
            closed_reason: source?.closed_reason ?? '',
            logo_media_id: source?.logo_media_id ?? null,
            logo_preview_url: source?.logo?.url ?? '',
            address: {
                ...(source?.address ?? {}),
                label: source?.address?.label ?? 'Ресторан',
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
        const addressValue = (settingsForm.value.address.value || settingsForm.value.address.line1 || '').trim();
        const prepTimeMin = prepTimeFieldValue(settingsForm.value.prep_time_min);
        const prepTimeMax = prepTimeFieldValue(settingsForm.value.prep_time_max);

        if (!name || !addressValue || settingsForm.value.address.lat == null || settingsForm.value.address.lng == null) {
            feedback.failure('Заполните название ресторана и основной адрес');
            return;
        }

        if ((prepTimeMin && Number(prepTimeMin) < 0) || (prepTimeMax && Number(prepTimeMax) < 0)) {
            feedback.failure('Время приготовления не может быть отрицательным');
            return;
        }

        if (prepTimeMin && prepTimeMax && Number(prepTimeMax) < Number(prepTimeMin)) {
            feedback.failure('Максимальное время приготовления не может быть меньше минимального');
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
                accepts_orders: settingsForm.value.accepts_orders,
                timezone: settingsForm.value.timezone || 'Europe/Moscow',
                opens_at: settingsForm.value.opens_at || null,
                closes_at: settingsForm.value.closes_at || null,
                closed_reason: settingsForm.value.closed_reason.trim() || null,
                prep_time_min: prepTimeMin ? Number(prepTimeMin) : null,
                prep_time_max: prepTimeMax ? Number(prepTimeMax) : null,
                logo_media_id: logoMediaId,
                address: {
                    ...settingsForm.value.address,
                    label: settingsForm.value.address.label?.trim() || null,
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

    const handleReady = async (order: Order) => {
        if (!canRestaurantMarkReadyOrder(order) || actionOrderId.value) return;

        actionOrderId.value = order.id;
        try {
            await markReady(slug.value, order.id);
            feedback.success('Заказ готов к выдаче');
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

    const createForm = ref<ProductFormState>(emptyProductForm());
    const createProductImageFiles = ref<File[]>([]);
    const createProductImagePreviews = ref<string[]>([]);

    const creatingProduct = ref(false);
    const productActionId = ref<number | null>(null);
    const editingProductId = ref<number | null>(null);
    const editForm = ref<ProductFormState>(emptyProductForm());
    const editProductImageFiles = ref<File[]>([]);
    const editProductImagePreviews = ref<string[]>([]);
    const savingProductEdit = ref(false);

    const resetCreateForm = () => {
        createForm.value = emptyProductForm();
        createProductImageFiles.value = [];
        revokeObjectUrls(createProductImagePreviews.value);
        createProductImagePreviews.value = [];
    };

    const handleCreateProductImageChange = (event: Event) => {
        const input = event.target as HTMLInputElement | null;
        const files = Array.from(input?.files ?? []);

        if (files.length > PRODUCT_IMAGE_LIMIT) {
            feedback.failure(`Можно загрузить не больше ${PRODUCT_IMAGE_LIMIT} фото`);
        }

        createProductImageFiles.value = files.slice(0, PRODUCT_IMAGE_LIMIT);

        revokeObjectUrls(createProductImagePreviews.value);
        createProductImagePreviews.value = createProductImageFiles.value.map((file) => URL.createObjectURL(file));
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

            if (createProductImageFiles.value.length) {
                const images: ProductImageRef[] = [];

                for (const [index, file] of createProductImageFiles.value.entries()) {
                    const media = await uploadMedia(file);
                    const image = await addProductImage(slug.value, newProduct.id, {
                        media_id: media.id,
                        is_cover: index === 0,
                        sort_order: index,
                    });
                    images.push(image);
                }

                nextProduct = {
                    ...newProduct,
                    images,
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

    const productToForm = (product: Product): ProductFormState => ({
        name: product.name,
        price: String(product.price),
        category_id: String(product.category?.id ?? product.category_id ?? ''),
        description: product.description ?? '',
        is_active: product.is_active,
    });

    const replaceProduct = (updated: Product) => {
        products.value = products.value.map((item) =>
            item.id === updated.id
                ? {
                    ...item,
                    ...updated,
                    category: updated.category ?? item.category,
                    images: updated.images ?? item.images,
                }
                : item,
        );
    };

    const startEditProduct = (product: Product) => {
        if (savingProductEdit.value || productActionId.value) return;

        editingProductId.value = product.id;
        editForm.value = productToForm(product);
        editProductImageFiles.value = [];
        revokeObjectUrls(editProductImagePreviews.value);
        editProductImagePreviews.value = [];
        showCreateProductForm.value = false;
    };

    const cancelEditProduct = () => {
        editingProductId.value = null;
        editForm.value = emptyProductForm();
        editProductImageFiles.value = [];
        revokeObjectUrls(editProductImagePreviews.value);
        editProductImagePreviews.value = [];
    };

    const handleEditProductImageChange = (product: Product, event: Event) => {
        const input = event.target as HTMLInputElement | null;
        const files = Array.from(input?.files ?? []);
        const currentImageCount = product.images?.length ?? 0;
        const availableSlots = Math.max(PRODUCT_IMAGE_LIMIT - currentImageCount, 0);

        if (files.length > availableSlots) {
            feedback.failure(
                availableSlots > 0
                    ? `Можно добавить ещё ${availableSlots} фото`
                    : `У блюда уже есть ${PRODUCT_IMAGE_LIMIT} фото`,
            );
        }

        editProductImageFiles.value = files.slice(0, availableSlots);

        revokeObjectUrls(editProductImagePreviews.value);
        editProductImagePreviews.value = editProductImageFiles.value.map((file) => URL.createObjectURL(file));
    };

    const handleUpdateProduct = async (product: Product) => {
        if (savingProductEdit.value || productActionId.value) return;

        const name = editForm.value.name.trim();
        const priceNum = Number(editForm.value.price);
        const catIdNum = Number(editForm.value.category_id) || 0;

        if (!name || Number.isNaN(priceNum) || priceNum <= 0 || !catIdNum) {
            feedback.failure('Заполните название, цену и категорию');
            return;
        }

        savingProductEdit.value = true;
        productActionId.value = product.id;

        try {
            const updated = await updateProduct(slug.value, product.id, {
                name,
                price: priceNum,
                category_id: catIdNum,
                description: editForm.value.description.trim() || null,
                is_active: editForm.value.is_active,
            });

            const existingImages = updated.images?.length ? updated.images : product.images ?? [];
            const uploadedImages: ProductImageRef[] = [];

            if (editProductImageFiles.value.length) {
                for (const [index, file] of editProductImageFiles.value.entries()) {
                    const media = await uploadMedia(file);
                    const image = await addProductImage(slug.value, product.id, {
                        media_id: media.id,
                        is_cover: existingImages.length === 0 && index === 0,
                        sort_order: existingImages.length + index,
                    });
                    uploadedImages.push(image);
                }
            }

            replaceProduct({
                ...updated,
                images: [...existingImages, ...uploadedImages],
            });

            feedback.success('Блюдо обновлено');
            cancelEditProduct();
        } catch {
        } finally {
            savingProductEdit.value = false;
            productActionId.value = null;
        }
    };

    const handleSetProductCover = async (product: Product, image: ProductImageRef) => {
        if (productActionId.value || image.is_cover) return;

        productActionId.value = product.id;

        try {
            const updatedImage = await updateProductImage(slug.value, product.id, image.id, {
                is_cover: true,
            });

            replaceProduct({
                ...product,
                images: (product.images ?? []).map((item) =>
                    item.id === updatedImage.id
                        ? updatedImage
                        : {
                            ...item,
                            is_cover: false,
                        },
                ),
            });
            feedback.success('Обложка обновлена');
        } catch {
        } finally {
            productActionId.value = null;
        }
    };

    const handleDeleteProductImage = async (product: Product, image: ProductImageRef) => {
        if (productActionId.value) return;

        productActionId.value = product.id;

        try {
            await deleteProductImage(slug.value, product.id, image.id);
            let nextImages = (product.images ?? []).filter((item) => item.id !== image.id);
            const nextImage = nextImages[0];

            if (image.is_cover && nextImage) {
                const nextCover = await updateProductImage(slug.value, product.id, nextImage.id, {
                    is_cover: true,
                });
                nextImages = nextImages.map((item) =>
                    item.id === nextCover.id
                        ? nextCover
                        : {
                            ...item,
                            is_cover: false,
                        },
                );
            }

            replaceProduct({
                ...product,
                images: nextImages,
            });
            feedback.success('Фото удалено');
        } catch {
        } finally {
            productActionId.value = null;
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
    const staffRoleOptions: Array<{
        value: Exclude<RestaurantRole, 'OWNER'>;
        label: string;
    }> = [
        { value: 'MANAGER', label: 'Менеджер' },
        { value: 'STAFF', label: 'Сотрудник' },
    ];

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

    const handleChangeStaffRole = async (
        member: RestaurantStaffMember,
        role: Exclude<RestaurantRole, 'OWNER'>,
    ) => {
        if (staffActionUserId.value) return;
        if (member.role === role || member.role === 'OWNER') return;

        staffActionUserId.value = member.id;
        try {
            await updateStaff(slug.value, member.id, {
                role,
            });
            feedback.success('Роль обновлена');
        } catch {
            try {
                await fetchStaff(slug.value);
            } catch {
            }
        } finally {
            staffActionUserId.value = null;
        }
    };

    const handleStaffRoleSelectChange = (
        member: RestaurantStaffMember,
        event: Event,
    ) => {
        const select = event.target as HTMLSelectElement;
        const role = select.value as Exclude<RestaurantRole, 'OWNER'>;
        select.value = member.role;

        void handleChangeStaffRole(member, role);
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

        const firstAvailableTab = availableTabs.value[0];
        if (firstAvailableTab && !availableTabs.value.includes(activeTab.value)) {
            activeTab.value = firstAvailableTab;
        }
    };

    onMounted(init);
    onBeforeUnmount(() => {
        revokeObjectUrls(createProductImagePreviews.value);
        revokeObjectUrls(editProductImagePreviews.value);
    });

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
        workingHoursText,
        availabilityText,
        availabilityStatus,
        settingsPrepAverageText,
        restaurantTimezones: RESTAURANT_TIMEZONES,
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
        createProductImagePreviews,
        imageUploading,
        creatingProduct,
        productActionId,
        editingProductId,
        editForm,
        editProductImagePreviews,
        savingProductEdit,
        productImageLimit: PRODUCT_IMAGE_LIMIT,
        inviteRole,
        inviteExpiryMinutes,
        latestStaffInvite,
        staffActionUserId,
        staffRoleOptions,
        actionOrderId,
        invitesLoading,
        settingsForm,
        settingsUploadInputKey,
        savingSettings,
        handleAccept,
        handleReady,
        handleCancel,
        handleSettingsLogoChange,
        handleSaveSettings,
        handleCreateProduct,
        handleCreateProductImageChange,
        startEditProduct,
        cancelEditProduct,
        handleEditProductImageChange,
        handleUpdateProduct,
        handleSetProductCover,
        handleDeleteProductImage,
        toggleProductActive,
        handleDeleteProduct,
        handleCreateStaffInvite,
        handleChangeStaffRole,
        handleStaffRoleSelectChange,
        handleRemoveStaff,
        formatPrice,
        formatDateTime,
        getOrderStatusClass,
        getOrderStatusLabel,
        getRestaurantOrderNextStep,
        getPaymentMethodLabel,
        getPaymentStatusLabel,
        canRestaurantAcceptOrder,
        canRestaurantMarkReadyOrder,
        canRestaurantCancelOrder,
        getRestaurantActivityLabel,
        getRestaurantActivityStatus,
    };
}

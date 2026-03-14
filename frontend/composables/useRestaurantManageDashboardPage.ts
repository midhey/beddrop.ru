import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from '#app';
import { useSeoMeta } from '#imports';
import { useFeedback } from '~/composables/useFeedback';
import { useRestaurants, type Restaurant } from '~/composables/useRestaurants';
import {
    useRestaurantProducts,
    type Product,
    type ProductPayload,
} from '~/composables/useRestaurantProducts';
import { useRestaurantOrders } from '~/composables/useRestaurantOrders';
import {
    useRestaurantStaff,
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
    } = useRestaurantProducts();

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
        addStaff,
        updateStaff,
        removeStaff,
    } = useRestaurantStaff();

    const activeTab = ref<TabKey>('menu');

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
            products.value = [newProduct, ...products.value];
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
        try {
            const updated = await updateProduct(slug.value, product.id, {
                is_active: !product.is_active,
            });
            products.value = products.value.map((item) =>
                item.id === updated.id ? updated : item,
            );
        } catch {
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

    const newStaffUserId = ref('');
    const newStaffRole = ref<RestaurantRole>('STAFF');
    const staffActionUserId = ref<number | null>(null);

    const handleAddStaff = async () => {
        const userIdNum = Number(newStaffUserId.value);
        if (!userIdNum || Number.isNaN(userIdNum)) {
            feedback.failure('Укажите корректный ID пользователя');
            return;
        }

        try {
            await addStaff(slug.value, {
                user_id: userIdNum,
                role: newStaffRole.value,
            });
            feedback.success('Сотрудник добавлен');
            newStaffUserId.value = '';
            newStaffRole.value = 'STAFF';
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

        try {
            await fetchProducts(slug.value);
        } catch {
        }

        try {
            await fetchRestaurantOrders(slug.value);
        } catch {
        }

        try {
            await fetchStaff(slug.value);
        } catch {
        }
    };

    onMounted(init);

    return {
        restaurant,
        activeTab,
        baseLoading,
        errorMessage,
        fullAddress,
        prepTimeText,
        hasProducts,
        hasOrders,
        products,
        restaurantOrders,
        staff,
        ordersLoading,
        showCreateProductForm,
        createForm,
        creatingProduct,
        productActionId,
        newStaffUserId,
        newStaffRole,
        staffActionUserId,
        actionOrderId,
        handleAccept,
        handleCancel,
        handleCreateProduct,
        toggleProductActive,
        handleDeleteProduct,
        handleAddStaff,
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

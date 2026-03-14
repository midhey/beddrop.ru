import { ref, reactive, computed, onMounted } from 'vue';
import { navigateTo } from '#app';
import { useFeedback } from '~/composables/useFeedback';
import { useAuthStore } from '~/stores/auth';
import {
    formatPhoneForDisplay,
    normalizePhoneInput,
    PHONE_MASK_OPTIONS,
} from '~/utils/phone';

type EditableField = 'name' | 'email' | 'phone';

export function useProfilePage() {
    const authStore = useAuthStore();
    const feedback = useFeedback();

    const user = computed(() => authStore.user);
    const isLoading = computed(() => authStore.loading);

    const userInitial = computed(() => {
        const src = authStore.user?.name || authStore.user?.email || 'U';
        return src.trim().charAt(0).toUpperCase();
    });

    // какие поля сейчас редактируются
    const isEditing = reactive({
        name: false,
        email: false,
        phone: false,
    });

    // локальные значения для форм
    const nameInput = ref('');
    const emailInput = ref('');
    const phoneInput = ref('');

    // refs на формы для template bindings
    const nameFormRef = ref<HTMLFormElement | null>(null);
    const emailFormRef = ref<HTMLFormElement | null>(null);
    const phoneFormRef = ref<HTMLFormElement | null>(null);

    // Смена пароля
    const isPasswordFormVisible = ref(false);
    const passwordFormRef = ref<HTMLFormElement | null>(null);
    const currentPassword = ref('');
    const newPassword = ref('');
    const newPasswordConfirmation = ref('');

    const resetPasswordForm = () => {
        currentPassword.value = '';
        newPassword.value = '';
        newPasswordConfirmation.value = '';
    };

    const init = async () => {
        if (authStore.accessToken && !authStore.user) {
            try {
                await authStore.profile(true);
            } catch {
            }
        }

        if (!authStore.accessToken) {
            feedback.info('Авторизуйтесь, чтобы просмотреть профиль');
            await navigateTo('/');
        }
    };

    onMounted(init);

    const resetEditing = () => {
        isEditing.name = false;
        isEditing.email = false;
        isEditing.phone = false;
    };

    const startEdit = (field: EditableField) => {
        if (!user.value) return;

        resetEditing();

        if (field === 'name') {
            nameInput.value = user.value.name || '';
            isEditing.name = true;
        }

        if (field === 'email') {
            emailInput.value = user.value.email;
            isEditing.email = true;
        }

        if (field === 'phone') {
            phoneInput.value = formatPhoneForDisplay(user.value.phone, '');
            isEditing.phone = true;
        }
    };

    const cancelEdit = (field: EditableField) => {
        isEditing[field] = false;
    };

    const withBlock = async (
        fn: () => Promise<void>,
        loadingLabel = 'Отправка...',
    ) => feedback.withBlock('.profile__block', fn, loadingLabel);

    const openPasswordForm = () => {
        resetPasswordForm();
        isPasswordFormVisible.value = true;
    };

    const closePasswordForm = () => {
        resetPasswordForm();
        isPasswordFormVisible.value = false;
    };

    const submitName = async () => {
        if (!user.value) return;

        await withBlock(async () => {
            await authStore.updateProfile({ name: nameInput.value || null });
            isEditing.name = false;
        }, 'Сохраняем...');
    };

    const submitEmail = async () => {
        if (!user.value) return;

        await withBlock(async () => {
            await authStore.updateProfile({ email: emailInput.value });
            isEditing.email = false;
        }, 'Сохраняем...');
    };

    const formatPhone = (value: string | null) => {
        return formatPhoneForDisplay(value);
    };

    const submitPhone = async () => {
        if (!user.value) return;

        await withBlock(async () => {
            await authStore.updateProfile({
                phone: normalizePhoneInput(phoneInput.value),
            });
            isEditing.phone = false;
        }, 'Сохраняем...');
    };

    const submitPassword = async () => {
        if (!user.value) return;

        await withBlock(async () => {
            await authStore.changePassword({
                current_password: currentPassword.value,
                password: newPassword.value,
                password_confirmation: newPasswordConfirmation.value,
            });

            closePasswordForm();
        }, 'Сохраняем...');
    };

    return {
        // state
        user,
        isLoading,
        isEditing,
        userInitial,

        // inputs
        nameInput,
        emailInput,
        phoneInput,

        // form refs
        nameFormRef,
        emailFormRef,
        phoneFormRef,

        // password block
        isPasswordFormVisible,
        passwordFormRef,
        currentPassword,
        newPassword,
        newPasswordConfirmation,

        //mask
        phoneMask: PHONE_MASK_OPTIONS,
        formatPhone,

        // actions
        startEdit,
        cancelEdit,
        submitName,
        submitEmail,
        submitPhone,
        submitPassword,
        openPasswordForm,
        closePasswordForm,
    };
}

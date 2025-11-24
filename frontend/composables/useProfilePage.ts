import { ref, reactive, computed, onMounted } from 'vue';
import { navigateTo, useNuxtApp } from '#app';
import { useAuthStore } from '~/stores/auth';

type EditableField = 'name' | 'email' | 'phone';

export function useProfilePage() {
    const authStore = useAuthStore();
    const { $notify, $block } = useNuxtApp();

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

    // refs на формы (для $block)
    const nameFormRef = ref<HTMLFormElement | null>(null);
    const emailFormRef = ref<HTMLFormElement | null>(null);
    const phoneFormRef = ref<HTMLFormElement | null>(null);

    // Смена пароля
    const passwordFormRef = ref<HTMLFormElement | null>(null);
    const currentPassword = ref('');
    const newPassword = ref('');
    const newPasswordConfirmation = ref('');

    const init = async () => {
        if (authStore.accessToken && !authStore.user) {
            try {
                await authStore.profile(true);
            } catch {
            }
        }

        if (!authStore.accessToken) {
            $notify?.info?.('Авторизуйтесь, чтобы просмотреть профиль');
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
            phoneInput.value = user.value.phone;
            isEditing.phone = true;
        }
    };

    const cancelEdit = (field: EditableField) => {
        isEditing[field] = false;
    };

    const withBlock = async (
        formRef: typeof nameFormRef,
        fn: () => Promise<void>,
        loadingLabel = 'Отправка...'
    ) => {
        const el = '.profile__block';
        if (el) {
            $block?.circle(el, loadingLabel);
        }

        try {
            await fn();
        } finally {
            if (el) {
                $block?.remove(el);
            }
        }
    };

    const submitName = async () => {
        if (!user.value) return;

        await withBlock(nameFormRef, async () => {
            await authStore.updateProfile({ name: nameInput.value || null });
            isEditing.name = false;
        }, 'Сохраняем...');
    };

    const submitEmail = async () => {
        if (!user.value) return;

        await withBlock(emailFormRef, async () => {
            await authStore.updateProfile({ email: emailInput.value });
            isEditing.email = false;
        }, 'Сохраняем...');
    };

    const formatPhone = (value: string | null) => {
        if (!value) return 'Не указано';

        const digits = value.replace(/\D/g, '');

        if (digits.length !== 11) return value;

        return `+7 ${digits.slice(1,4)}-${digits.slice(4,7)}-${digits.slice(7,9)}-${digits.slice(9,11)}`;
    };

    const submitPhone = async () => {
        if (!user.value) return;

        await withBlock(phoneFormRef, async () => {
            await authStore.updateProfile({
                phone: phoneInput.value.replace(/\D/g, ''),
            });
            isEditing.phone = false;
        }, 'Сохраняем...');
    };

    const submitPassword = async () => {
        if (!user.value) return;

        await withBlock(passwordFormRef, async () => {
            await authStore.changePassword({
                current_password: currentPassword.value,
                password: newPassword.value,
                password_confirmation: newPasswordConfirmation.value,
            });

            currentPassword.value = '';
            newPassword.value = '';
            newPasswordConfirmation.value = '';
        }, 'Сохраняем...');
    };

    const phoneMask = {
        mask: '+{7} 000 000-00-00',
        lazy: false,
        overwrite: true,
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
        passwordFormRef,
        currentPassword,
        newPassword,
        newPasswordConfirmation,

        //mask
        phoneMask,
        formatPhone,

        // actions
        startEdit,
        cancelEdit,
        submitName,
        submitEmail,
        submitPhone,
        submitPassword,
    };
}
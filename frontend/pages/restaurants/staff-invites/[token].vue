<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CheckCircle2, Clock3, Link2, ShieldCheck } from 'lucide-vue-next';
import LoginForm from '~/components/auth/LoginForm.vue';
import RegisterForm from '~/components/auth/RegisterForm.vue';
import { useRestaurantStaff, type RestaurantStaffInvite } from '~/composables/useRestaurantStaff';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const appShellStore = useAppShellStore();
const feedback = useFeedback();

const token = computed(() => String(route.params.token || ''));
const authMode = ref<'login' | 'register'>('login');
const invite = ref<RestaurantStaffInvite | null>(null);
const inviteLoading = ref(true);
const acceptLoading = ref(false);
const acceptAttempted = ref(false);

const {
  fetchInvite,
  acceptInvite,
} = useRestaurantStaff();

const roleLabels = {
  OWNER: 'Владелец',
  MANAGER: 'Менеджер',
  STAFF: 'Сотрудник',
} as const;

const inviteStatus = computed(() => {
  if (!invite.value) {
    return 'missing';
  }

  if (invite.value.accepted_at) {
    return 'accepted';
  }

  if (new Date(invite.value.expires_at).getTime() < Date.now()) {
    return 'expired';
  }

  return 'active';
});

const loadInvite = async () => {
  inviteLoading.value = true;

  try {
    invite.value = await fetchInvite(token.value);
  } catch {
    invite.value = null;
  } finally {
    inviteLoading.value = false;
  }
};

const acceptCurrentInvite = async () => {
  if (!authStore.isAuthenticated || !invite.value || inviteStatus.value !== 'active' || acceptLoading.value || acceptAttempted.value) {
    return;
  }

  acceptLoading.value = true;
  acceptAttempted.value = true;

  try {
    invite.value = await acceptInvite(token.value);
    await authStore.profile(true).catch(() => null);
    await appShellStore.ensureBootstrapped(true);
    feedback.success('Приглашение принято');
  } catch {
    acceptAttempted.value = false;
  } finally {
    acceptLoading.value = false;
  }
};

watch(
  () => authStore.isAuthenticated,
  async (isAuthenticated) => {
    if (isAuthenticated && !authStore.user) {
      await authStore.profile(true).catch(() => null);
    }

    if (isAuthenticated) {
      await acceptCurrentInvite();
    }
  },
  { immediate: true },
);

await loadInvite();

if (authStore.isAuthenticated) {
  await acceptCurrentInvite();
}
</script>

<template>
  <section class="staff-invite-page page-shell">
    <div class="staff-invite-page__container">
      <div class="staff-invite-page__hero">
        <span class="staff-invite-page__eyebrow">Приглашение в команду</span>
        <h1 class="staff-invite-page__title">
          Вступление в команду ресторана
        </h1>
        <p class="staff-invite-page__subtitle">
          Если ссылка ещё активна, вы сможете принять приглашение и сразу получить доступ к кабинету ресторана.
        </p>
      </div>

      <div
          v-if="inviteLoading"
          class="staff-invite-page__state state-message state-message--loading"
      >
        Проверяем приглашение...
      </div>

      <div
          v-else-if="!invite"
          class="staff-invite-page__state state-message state-message--error"
      >
        Приглашение не найдено или уже недоступно.
      </div>

      <div
          v-else
          class="staff-invite-page__layout"
      >
        <article class="staff-invite-page__card">
          <div class="staff-invite-page__card-top">
            <span
                class="status-chip"
                :class="inviteStatus === 'active' ? 'status-chip--success' : inviteStatus === 'accepted' ? 'status-chip--info' : 'status-chip--danger'"
            >
              {{ inviteStatus === 'active' ? 'Активно' : inviteStatus === 'accepted' ? 'Уже принято' : 'Истекло' }}
            </span>
            <span class="staff-invite-page__restaurant">
              {{ invite.restaurant.name }}
            </span>
          </div>

          <div class="staff-invite-page__facts">
            <div class="staff-invite-page__fact">
              <ShieldCheck class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
              <div>
                <span class="staff-invite-page__fact-label">Роль</span>
                <strong class="staff-invite-page__fact-value">{{ roleLabels[invite.role] }}</strong>
              </div>
            </div>

            <div class="staff-invite-page__fact">
              <Clock3 class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
              <div>
                <span class="staff-invite-page__fact-label">Действует до</span>
                <strong class="staff-invite-page__fact-value">{{ new Date(invite.expires_at).toLocaleString('ru-RU') }}</strong>
              </div>
            </div>

            <div
                v-if="invite.invited_by"
                class="staff-invite-page__fact"
            >
              <Link2 class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
              <div>
                <span class="staff-invite-page__fact-label">Кто пригласил</span>
                <strong class="staff-invite-page__fact-value">
                  {{ invite.invited_by.name || invite.invited_by.email }}
                </strong>
              </div>
            </div>
          </div>

          <div
              v-if="inviteStatus === 'accepted'"
              class="staff-invite-page__success"
          >
            <CheckCircle2 class="ui-icon" :size="20" :stroke-width="1.9" aria-hidden="true" />
            <span>Приглашение уже использовано. Доступ к ресторану должен появиться в кабинете.</span>
          </div>

          <div
              v-else-if="authStore.isAuthenticated"
              class="staff-invite-page__actions"
          >
            <button
                type="button"
                class="button"
                :disabled="inviteStatus !== 'active' || acceptLoading"
                @click="acceptCurrentInvite"
            >
              {{ acceptLoading ? 'Подключаем...' : inviteStatus === 'active' ? 'Принять приглашение' : 'Приглашение недоступно' }}
            </button>

            <button
                v-if="inviteStatus === 'accepted'"
                type="button"
                class="button button--ghost"
                @click="router.push(`/restaurants/manage/${invite.restaurant.slug}`)"
            >
              Перейти в кабинет
            </button>
          </div>

          <p
              v-else
              class="staff-invite-page__hint"
          >
            Войдите или зарегистрируйтесь, чтобы принять приглашение.
          </p>
        </article>

        <article
            v-if="!authStore.isAuthenticated && inviteStatus === 'active'"
            class="staff-invite-page__auth"
        >
          <div class="staff-invite-page__auth-switch">
            <button
                type="button"
                class="staff-invite-page__auth-tab"
                :class="{ 'staff-invite-page__auth-tab--active': authMode === 'login' }"
                @click="authMode = 'login'"
            >
              Вход
            </button>
            <button
                type="button"
                class="staff-invite-page__auth-tab"
                :class="{ 'staff-invite-page__auth-tab--active': authMode === 'register' }"
                @click="authMode = 'register'"
            >
              Регистрация
            </button>
          </div>

          <div class="staff-invite-page__auth-body">
            <LoginForm
                v-if="authMode === 'login'"
                @success="acceptCurrentInvite"
                @open-register="authMode = 'register'"
            />
            <RegisterForm
                v-else
                @success="acceptCurrentInvite"
            />
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

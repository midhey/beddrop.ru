<script setup lang="ts">
import { Pencil, MapPin, ChevronRight, ShieldCheck } from 'lucide-vue-next';

useAppSeoMeta({
  title: 'Мой профиль — BedDrop',
  description: 'Профиль пользователя BedDrop: личные данные, контакты, пароль и переход к адресам доставки.',
  robots: 'noindex,nofollow',
});

const {
  user,
  isLoading,
  isEditing,
  userInitial,
  nameInput,
  emailInput,
  phoneInput,
  nameFormRef,
  emailFormRef,
  phoneFormRef,
  passwordFormRef,
  isPasswordFormVisible,
  currentPassword,
  newPassword,
  newPasswordConfirmation,
  phoneMask,
  formatPhone,
  startEdit,
  cancelEdit,
  submitName,
  submitEmail,
  submitPhone,
  submitPassword,
  openPasswordForm,
  closePasswordForm,
} = useProfilePage();
</script>

<template>
  <ClientOnly>
    <section class="profile">
      <div class="profile__container">
        <div
            v-if="user"
            class="profile__section"
        >
          <!-- Шапка профиля -->
          <div class="profile__header">
            <div class="profile__avatar-wrapper">
              <div class="profile__avatar">
                {{ userInitial }}
              </div>
            </div>
            <h1 class="profile__title">Мой профиль</h1>
          </div>

          <!-- Группа: Основные данные -->
          <div class="profile__group">
            <h2 class="profile__group-title">Личные данные</h2>
            <div class="profile__card">
              <div class="profile__info-list">
                <!-- Имя -->
                <div class="profile__info-item-wrapper">
                  <div
                      v-if="!isEditing.name"
                      class="profile__info-item"
                  >
                    <div class="profile__info-content">
                      <span class="profile__info-label">Имя</span>
                      <span class="profile__info-value">{{ user.name || 'Не указано' }}</span>
                    </div>
                    <button
                        type="button"
                        class="profile__edit-trigger"
                        title="Редактировать имя"
                        @click="startEdit('name')"
                    >
                      <Pencil :size="18" />
                    </button>
                  </div>
                  <div
                      v-else
                      class="profile__form-wrapper"
                  >
                    <form
                        ref="nameFormRef"
                        class="form"
                        @submit.prevent="submitName"
                    >
                      <div class="form__field">
                        <label class="form__label">
                          <input
                              v-model="nameInput"
                              type="text"
                              class="form__input"
                              placeholder="Имя"
                              autocomplete="name"
                              required
                          />
                          <span class="form__label-text">Ваше имя</span>
                        </label>
                      </div>
                      <div class="profile__form-actions">
                        <button
                            type="submit"
                            class="button"
                            :disabled="isLoading"
                        >
                          {{ isLoading ? 'Сохранение...' : 'Обновить' }}
                        </button>
                        <button
                            type="button"
                            class="button button--ghost"
                            @click="cancelEdit('name')"
                        >
                          Отмена
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Email -->
                <div class="profile__info-item-wrapper">
                  <div
                      v-if="!isEditing.email"
                      class="profile__info-item"
                  >
                    <div class="profile__info-content">
                      <span class="profile__info-label">Электронная почта</span>
                      <span class="profile__info-value">{{ user.email }}</span>
                    </div>
                    <button
                        type="button"
                        class="profile__edit-trigger"
                        title="Редактировать email"
                        @click="startEdit('email')"
                    >
                      <Pencil :size="18" />
                    </button>
                  </div>
                  <div
                      v-else
                      class="profile__form-wrapper"
                  >
                    <form
                        ref="emailFormRef"
                        class="form"
                        @submit.prevent="submitEmail"
                    >
                      <div class="form__field">
                        <label class="form__label">
                          <input
                              v-model="emailInput"
                              type="email"
                              class="form__input"
                              placeholder="Email"
                              autocomplete="email"
                              required
                          />
                          <span class="form__label-text">Электронная почта</span>
                        </label>
                      </div>
                      <div class="profile__form-actions">
                        <button
                            type="submit"
                            class="button"
                            :disabled="isLoading"
                        >
                          {{ isLoading ? 'Сохранение...' : 'Обновить' }}
                        </button>
                        <button
                            type="button"
                            class="button button--ghost"
                            @click="cancelEdit('email')"
                        >
                          Отмена
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Телефон -->
                <div class="profile__info-item-wrapper">
                  <div
                      v-if="!isEditing.phone"
                      class="profile__info-item"
                  >
                    <div class="profile__info-content">
                      <span class="profile__info-label">Номер телефона</span>
                      <span class="profile__info-value">{{ formatPhone(user.phone) }}</span>
                    </div>
                    <button
                        type="button"
                        class="profile__edit-trigger"
                        title="Редактировать телефон"
                        @click="startEdit('phone')"
                    >
                      <Pencil :size="18" />
                    </button>
                  </div>
                  <div
                      v-else
                      class="profile__form-wrapper"
                  >
                    <form
                        ref="phoneFormRef"
                        class="form"
                        @submit.prevent="submitPhone"
                    >
                      <div class="form__field">
                        <label class="form__label">
                          <input
                              v-model="phoneInput"
                              type="tel"
                              class="form__input"
                              placeholder="Телефон"
                              autocomplete="tel"
                              v-imask="phoneMask"
                              required
                          />
                          <span class="form__label-text">Номер телефона</span>
                        </label>
                      </div>
                      <div class="profile__form-actions">
                        <button
                            type="submit"
                            class="button"
                            :disabled="isLoading"
                        >
                          {{ isLoading ? 'Сохранение...' : 'Обновить' }}
                        </button>
                        <button
                            type="button"
                            class="button button--ghost"
                            @click="cancelEdit('phone')"
                        >
                          Отмена
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Адреса -->
          <NuxtLink
              to="/profile/addresses"
              class="profile__card profile__nav-card"
          >
            <div class="profile__nav-content">
              <div class="profile__nav-icon">
                <MapPin :size="24" />
              </div>
              <div class="profile__nav-copy">
                <span class="profile__nav-title">Мои адреса</span>
                <span class="profile__nav-subtitle">Управление сохраненными точками доставки</span>
              </div>
            </div>
            <ChevronRight class="profile__nav-chevron" :size="20" />
          </NuxtLink>

          <!-- Безопасность -->
          <div class="profile__group profile__security">
            <h2 class="profile__group-title">Конфиденциальность</h2>
            <div class="profile__card profile__security-card">
              <div class="profile__security-header">
                <div class="profile__security-info">
                  <div class="profile__security-title-row row">
                    <ShieldCheck :size="20" class="ui-icon" style="color: var(--color-success)" />
                    <h3 class="profile__security-title">Безопасность аккаунта</h3>
                  </div>
                  <p class="profile__security-text">
                    Регулярно меняйте пароль для обеспечения максимальной защиты ваших данных и заказов.
                  </p>
                </div>

                <button
                    v-if="!isPasswordFormVisible"
                    type="button"
                    class="button button--outline"
                    @click="openPasswordForm"
                >
                  Сменить пароль
                </button>
                <button
                    v-else
                    type="button"
                    class="button button--text"
                    @click="closePasswordForm"
                >
                  Скрыть форму
                </button>
              </div>

              <form
                  v-if="isPasswordFormVisible"
                  ref="passwordFormRef"
                  class="profile__password-form form"
                  @submit.prevent="submitPassword"
              >
                <div class="form__field">
                  <label class="form__label">
                    <input
                        v-model="currentPassword"
                        type="password"
                        class="form__input"
                        placeholder="Текущий пароль"
                        required
                        autocomplete="current-password"
                    />
                    <span class="form__label-text">Текущий пароль</span>
                  </label>
                </div>

                <div class="form-row">
                  <div class="form__field">
                    <label class="form__label">
                      <input
                          v-model="newPassword"
                          type="password"
                          class="form__input"
                          placeholder="Новый пароль"
                          required
                          autocomplete="new-password"
                      />
                      <span class="form__label-text">Новый пароль</span>
                    </label>
                  </div>

                  <div class="form__field">
                    <label class="form__label">
                      <input
                          v-model="newPasswordConfirmation"
                          type="password"
                          class="form__input"
                          placeholder="Повторите новый пароль"
                          required
                          autocomplete="new-password"
                      />
                      <span class="form__label-text">Повторите новый пароль</span>
                    </label>
                  </div>
                </div>

                <div class="profile__form-actions">
                  <button
                      type="submit"
                      class="button"
                      :disabled="isLoading"
                  >
                    {{ isLoading ? 'Сохранение...' : 'Обновить пароль' }}
                  </button>
                  <button
                      type="button"
                      class="button button--ghost"
                      @click="closePasswordForm"
                  >
                    Отмена
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div
            v-else
            class="profile__empty"
        >
          <div class="state-message state-message--loading">
            Загружаем ваш профиль...
          </div>
        </div>
      </div>
    </section>
  </ClientOnly>
</template>

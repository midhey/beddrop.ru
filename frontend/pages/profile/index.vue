<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

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
        <div class="profile__block">
          <h1>Мой профиль</h1>

          <div
              v-if="user"
              class="profile__card"
          >
            <div class="profile__column profile__column--avatar">
              <div class="profile__avatar">
                {{ userInitial }}
              </div>
            </div>

            <div class="profile__column profile__column--full-width">
              <div class="profile__row">
                <template v-if="!isEditing.name">
                  <div class="profile__field">
                    <div class="profile__box">
                      <span class="profile__label">Имя</span>
                      <span class="profile__value">
                  {{ user.name || 'User' }}
                </span>
                    </div>
                    <button
                        type="button"
                        class="profile__edit-btn button"
                        aria-label="Редактировать имя"
                        @click="startEdit('name')"
                    >
                      <Pencil class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
                    </button>
                  </div>
                </template>

                <template v-else>
                  <form
                      ref="nameFormRef"
                      class="form form--inline"
                      @submit.prevent="submitName"
                  >
                    <div class="form__field form__field--inline">
                      <label class="form__label">
                        <input
                            v-model="nameInput"
                            type="text"
                            class="form__input"
                            placeholder="Имя"
                            autocomplete="name"
                        />
                        <span class="form__label-text">Имя</span>
                      </label>
                    </div>

                    <div class="form__inline-actions">
                      <button
                          type="submit"
                          class="button"
                          :disabled="isLoading"
                      >
                        {{ isLoading ? 'Сохраняем...' : 'Сохранить' }}
                      </button>
                      <button
                          type="button"
                          class="button button--text"
                          @click="cancelEdit('name')"
                      >
                        Отмена
                      </button>
                    </div>
                  </form>
                </template>
              </div>

              <div class="profile__row">
                <template v-if="!isEditing.email">
                  <div class="profile__field">
                    <div class="profile__box">
                      <span class="profile__label">Email</span>
                      <span class="profile__value">
                    {{ user.email }}
                  </span>
                    </div>
                    <button
                        type="button"
                        class="profile__edit-btn button"
                        aria-label="Редактировать email"
                        @click="startEdit('email')"
                    >
                      <Pencil class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
                    </button>
                  </div>
                </template>

                <template v-else>
                  <form
                      ref="emailFormRef"
                      class="form form--inline"
                      @submit.prevent="submitEmail"
                  >
                    <div class="form__field form__field--inline">
                      <label class="form__label">
                        <input
                            v-model="emailInput"
                            type="email"
                            class="form__input"
                            placeholder="Электронная почта"
                            required
                            autocomplete="email"
                        />
                        <span class="form__label-text">Электронная почта</span>
                      </label>
                    </div>

                    <div class="form__inline-actions">
                      <button
                          type="submit"
                          class="button"
                          :disabled="isLoading"
                      >
                        {{ isLoading ? 'Сохраняем...' : 'Сохранить' }}
                      </button>
                      <button
                          type="button"
                          class="button button--text"
                          @click="cancelEdit('email')"
                      >
                        Отмена
                      </button>
                    </div>
                  </form>
                </template>
              </div>

              <div class="profile__row">
                <template v-if="!isEditing.phone">
                  <div class="profile__field">
                    <div class="profile__box">
                      <span class="profile__label">Телефон</span>
                      <span class="profile__value">
                    {{ formatPhone(user.phone) }}
                  </span>
                    </div>
                    <button
                        type="button"
                        class="profile__edit-btn button"
                        aria-label="Редактировать телефон"
                        @click="startEdit('phone')"
                    >
                      <Pencil class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
                    </button>
                  </div>
                </template>

                <template v-else>
                  <form
                      ref="phoneFormRef"
                      class="form form--inline"
                      @submit.prevent="submitPhone"
                  >
                    <div class="form__field form__field--inline">
                      <label class="form__label">
                        <input
                            v-model="phoneInput"
                            type="tel"
                            class="form__input"
                            placeholder="Телефон"
                            autocomplete="tel"
                            v-imask="phoneMask"
                        />
                        <span class="form__label-text">Телефон</span>
                      </label>
                    </div>

                    <div class="form__inline-actions">
                      <button
                          type="submit"
                          class="button"
                          :disabled="isLoading"
                      >
                        {{ isLoading ? 'Сохраняем...' : 'Сохранить' }}
                      </button>
                      <button
                          type="button"
                          class="button button--text"
                          @click="cancelEdit('phone')"
                      >
                        Отмена
                      </button>
                    </div>
                  </form>
                </template>
              </div>
            </div>
          </div>


          <NuxtLink
              to="/profile/addresses"
              class="button button--outline"
          >
            Управление адресами
          </NuxtLink>
          <div
              v-if="user"
              class="profile__card profile__card--password"
          >
            <div class="profile__security-head">
              <div class="profile__security-copy">
                <h2 class="profile__subtitle">Безопасность</h2>
                <p class="profile__hint">
                  Для защиты аккаунта пароль меняется только вручную по отдельной кнопке.
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
                Скрыть
              </button>
            </div>

            <form
                v-if="isPasswordFormVisible"
                ref="passwordFormRef"
                class="form form--password profile__password-form"
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

              <div class="profile__actions profile__actions--password">
                <button
                    type="submit"
                    class="button"
                    :disabled="isLoading"
                >
                  {{ isLoading ? 'Сохраняем...' : 'Изменить пароль' }}
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

          <div
              v-else
              class="profile__empty"
          >
            Загрузка профиля...
          </div>
        </div>
      </div>
    </section>
  </ClientOnly>
</template>

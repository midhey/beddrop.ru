<script setup lang="ts">
import { ArrowLeft, Clock3, Phone } from 'lucide-vue-next';
import { useRestaurantManageDashboardPage } from '~/composables/useRestaurantManageDashboardPage';

const {
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
} = useRestaurantManageDashboardPage();
</script>

<template>
  <section class="restaurant-dashboard page-shell">
    <div class="restaurant-dashboard__container">
      <button
          type="button"
          class="restaurant-dashboard__back page-back"
          @click="$router.back()"
      >
        <ArrowLeft class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
        <span>Назад</span>
      </button>

      <div class="restaurant-dashboard__header page-head">
        <div class="restaurant-dashboard__header-main">
          <h1 class="restaurant-dashboard__title page-title">
            Ресторан
            <span v-if="restaurant"> «{{ restaurant.name }}»</span>
          </h1>

          <div
              v-if="restaurant"
              class="restaurant-dashboard__meta"
          >
            <span
                class="restaurant-dashboard__status status-chip"
                :class="restaurant.is_active ? 'status-chip--success' : 'status-chip--danger'"
            >
              {{ getRestaurantActivityLabel(restaurant.is_active) }}
            </span>

            <span
                v-if="prepTimeText"
                class="restaurant-dashboard__badge status-chip status-chip--info"
            >
              <Clock3 class="ui-icon" :size="14" :stroke-width="1.9" aria-hidden="true" />
              {{ prepTimeText }}
            </span>

            <span
                v-if="restaurant.phone"
                class="restaurant-dashboard__badge status-chip status-chip--info"
            >
              <Phone class="ui-icon" :size="14" :stroke-width="1.9" aria-hidden="true" />
              {{ restaurant.phone }}
            </span>
          </div>

          <p
              v-if="restaurant"
              class="restaurant-dashboard__address page-subtitle"
          >
            {{ fullAddress }}
          </p>
        </div>
      </div>

      <!-- табы -->
      <div class="restaurant-dashboard__tabs">
        <button
            type="button"
            class="restaurant-dashboard__tab"
            :class="{ 'restaurant-dashboard__tab--active': activeTab === 'orders' }"
            @click="activeTab = 'orders'"
        >
          Заказы
        </button>
        <button
            type="button"
            class="restaurant-dashboard__tab"
            :class="{ 'restaurant-dashboard__tab--active': activeTab === 'menu' }"
            @click="activeTab = 'menu'"
        >
          Меню
        </button>
        <button
            type="button"
            class="restaurant-dashboard__tab"
            :class="{ 'restaurant-dashboard__tab--active': activeTab === 'staff' }"
            @click="activeTab = 'staff'"
        >
          Персонал
        </button>
      </div>

      <!-- общие стейты -->
      <div
          v-if="baseLoading"
          class="restaurant-dashboard__loading state-message state-message--loading"
      >
        Загрузка данных...
      </div>

      <div
          v-else-if="errorMessage"
          class="restaurant-dashboard__error state-message state-message--error"
      >
        {{ errorMessage }}
      </div>

      <div
          v-else-if="!restaurant"
          class="restaurant-dashboard__empty state-message state-message--empty"
      >
        Ресторан не найден
      </div>

      <!-- контент вкладок -->
      <div
          v-else
          class="restaurant-dashboard__content"
      >
        <!-- ВКЛАДКА: ЗАКАЗЫ -->
        <div
            v-if="activeTab === 'orders'"
            class="restaurant-dashboard__section surface-card"
        >
          <div class="restaurant-dashboard__section-header section-head">
            <h2 class="restaurant-dashboard__section-title section-title">
              Заказы ресторана
            </h2>
            <span class="restaurant-dashboard__section-meta section-meta">
              {{ hasOrders ? `${restaurantOrders.length} заказов` : 'Нет заказов' }}
            </span>
          </div>

          <div
              v-if="ordersLoading"
              class="restaurant-dashboard__section-empty state-message state-message--loading"
          >
            Загружаем заказы...
          </div>

          <p
              v-else-if="!hasOrders"
              class="restaurant-dashboard__section-empty state-message state-message--empty"
          >
            Пока нет заказов.
          </p>

          <div
              v-else
              class="restaurant-dashboard__orders"
          >
            <article
                v-for="order in restaurantOrders"
                :key="order.id"
                class="restaurant-dashboard__order"
            >
              <div class="restaurant-dashboard__order-main">
                <div class="restaurant-dashboard__order-top">
                  <span class="restaurant-dashboard__order-number">
                    Заказ #{{ order.id }}
                  </span>
                  <span
                      class="order-status status-chip"
                      :class="getOrderStatusClass(order.status)"
                  >
                    {{ getOrderStatusLabel(order.status, 'restaurant') }}
                  </span>
                </div>

                <div class="restaurant-dashboard__order-meta">
                  <span>
                    {{ order.items_count ?? '—' }} позиций
                  </span>
                  <span>
                    {{ formatDateTime(order.created_at) }}
                  </span>
                </div>

                <div class="restaurant-dashboard__order-pay">
                  <span>
                    {{ getPaymentMethodLabel(order.payment_method) }}
                  </span>
                  ·
                  <span>
                    {{ getPaymentStatusLabel(order.payment_status) }}
                  </span>
                </div>

                <p
                    v-if="order.comment"
                    class="restaurant-dashboard__order-comment"
                >
                  Комментарий: {{ order.comment }}
                </p>
              </div>

              <div class="restaurant-dashboard__order-right">
                <div class="restaurant-dashboard__order-total">
                  {{ formatPrice(order.total_price) }}
                </div>

                <div class="restaurant-dashboard__order-actions">
                  <button
                      type="button"
                      class="restaurant-dashboard__order-btn"
                      :disabled="!canRestaurantAcceptOrder(order) || actionOrderId === order.id"
                      @click="handleAccept(order)"
                  >
                    Принять
                  </button>

                  <button
                      type="button"
                      class="restaurant-dashboard__order-btn restaurant-dashboard__order-btn--danger"
                      :disabled="!canRestaurantCancelOrder(order) || actionOrderId === order.id"
                      @click="handleCancel(order)"
                  >
                    Отменить
                  </button>
                </div>
              </div>
            </article>
          </div>
        </div>

        <!-- ВКЛАДКА: МЕНЮ -->
        <div
            v-else-if="activeTab === 'menu'"
            class="restaurant-dashboard__section surface-card"
        >
          <div class="restaurant-dashboard__section-header section-head">
            <div>
              <h2 class="restaurant-dashboard__section-title section-title">
                Меню ресторана
              </h2>
              <span class="restaurant-dashboard__section-meta section-meta">
                {{ hasProducts ? `${products.length} позиций` : 'Нет позиций' }}
              </span>
            </div>

            <button
                type="button"
                class="restaurant-dashboard__action-btn"
                @click="showCreateProductForm = !showCreateProductForm"
            >
              {{ showCreateProductForm ? 'Скрыть форму' : 'Добавить блюдо' }}
            </button>
          </div>

          <!-- форма добавления блюда -->
          <div
              v-if="showCreateProductForm"
              class="restaurant-dashboard__create-product surface-card--soft"
          >
            <div class="restaurant-dashboard__form-row form-field">
              <label class="restaurant-dashboard__form-label">
                Название
              </label>
              <input
                  v-model="createForm.name"
                  type="text"
                  class="restaurant-dashboard__form-input field-input"
                  placeholder="Например, Маргарита"
              >
            </div>

            <div class="restaurant-dashboard__form-row form-field">
              <label class="restaurant-dashboard__form-label">
                Цена (₽)
              </label>
              <input
                  v-model="createForm.price"
                  type="number"
                  min="0"
                  step="1"
                  class="restaurant-dashboard__form-input field-input"
              >
            </div>

            <div class="restaurant-dashboard__form-row form-field">
              <label class="restaurant-dashboard__form-label">
                ID категории
              </label>
              <input
                  v-model="createForm.category_id"
                  type="number"
                  min="1"
                  step="1"
                  class="restaurant-dashboard__form-input field-input"
                  placeholder="Например, 1"
              >
            </div>

            <div class="restaurant-dashboard__form-row form-field">
              <label class="restaurant-dashboard__form-label">
                Описание
              </label>
              <textarea
                  v-model="createForm.description"
                  rows="2"
                  class="restaurant-dashboard__form-textarea field-textarea"
              ></textarea>
            </div>

            <div class="restaurant-dashboard__form-row restaurant-dashboard__form-row--inline form-field">
              <label class="restaurant-dashboard__form-checkbox-label">
                <input
                    v-model="createForm.is_active"
                    type="checkbox"
                >
                <span>Отображать в меню</span>
              </label>
            </div>

            <div class="restaurant-dashboard__form-actions form-actions">
              <button
                  type="button"
                  class="restaurant-dashboard__order-btn"
                  :disabled="creatingProduct"
                  @click="handleCreateProduct"
              >
                {{ creatingProduct ? 'Добавляем...' : 'Создать блюдо' }}
              </button>
            </div>
          </div>

          <p
              v-if="!hasProducts"
              class="restaurant-dashboard__section-empty state-message state-message--empty"
          >
            В меню пока нет блюд.
          </p>

          <ul
              v-else
              class="restaurant-dashboard__products"
          >
            <li
                v-for="product in products"
                :key="product.id"
                class="restaurant-dashboard__product"
            >
              <div class="restaurant-dashboard__product-main">
                <div class="restaurant-dashboard__product-top">
                  <span class="restaurant-dashboard__product-name">
                    {{ product.name }}
                  </span>
                  <span class="restaurant-dashboard__product-price">
                    {{ formatPrice(product.price) }}
                  </span>
                </div>

                <p
                    v-if="product.description"
                    class="restaurant-dashboard__product-desc"
                >
                  {{ product.description }}
                </p>

                <div class="restaurant-dashboard__product-meta">
                  <span class="restaurant-dashboard__product-category">
                    {{ product.category?.name || 'Без категории' }}
                  </span>
                  <span
                      class="restaurant-dashboard__product-status"
                      :class="[
                        'status-chip',
                        product.is_active ? 'status-chip--info' : 'status-chip--danger',
                      ]"
                  >
                    {{ product.is_active ? 'Показывается в меню' : 'Скрыт' }}
                  </span>
                </div>
              </div>

              <div class="restaurant-dashboard__product-actions">
                <button
                    type="button"
                    class="restaurant-dashboard__order-btn"
                    :disabled="productActionId === product.id"
                    @click="toggleProductActive(product)"
                >
                  {{ product.is_active ? 'Скрыть' : 'Показать' }}
                </button>

                <button
                    type="button"
                    class="restaurant-dashboard__order-btn restaurant-dashboard__order-btn--danger"
                    :disabled="productActionId === product.id"
                    @click="handleDeleteProduct(product)"
                >
                  Удалить
                </button>
              </div>
            </li>
          </ul>
        </div>

        <!-- ВКЛАДКА: ПЕРСОНАЛ -->
        <div
            v-else-if="activeTab === 'staff'"
            class="restaurant-dashboard__section surface-card"
        >
          <div class="restaurant-dashboard__section-header section-head">
            <h2 class="restaurant-dashboard__section-title section-title">
              Персонал ресторана
            </h2>
            <span class="restaurant-dashboard__section-meta section-meta">
              {{ staff.length ? `${staff.length} сотрудников` : 'Сотрудники не добавлены' }}
            </span>
          </div>

          <!-- форма добавления сотрудника -->
          <div class="restaurant-dashboard__create-staff surface-card--soft">
            <div class="restaurant-dashboard__form-row form-field">
              <label class="restaurant-dashboard__form-label">
                ID пользователя
              </label>
              <input
                  v-model="newStaffUserId"
                  type="number"
                  min="1"
                  step="1"
                  class="restaurant-dashboard__form-input field-input"
                  placeholder="Например, 5"
              >
            </div>

            <div class="restaurant-dashboard__form-row form-field">
              <label class="restaurant-dashboard__form-label">
                Роль
              </label>
              <select
                  v-model="newStaffRole"
                  class="restaurant-dashboard__form-input field-select"
              >
                <option value="OWNER">
                  Владелец
                </option>
                <option value="MANAGER">
                  Менеджер
                </option>
                <option value="STAFF">
                  Сотрудник
                </option>
              </select>
            </div>

            <div class="restaurant-dashboard__form-actions form-actions">
              <button
                  type="button"
                  class="restaurant-dashboard__order-btn"
                  @click="handleAddStaff"
              >
                Добавить сотрудника
              </button>
            </div>
          </div>

          <p
              v-if="!staff.length"
              class="restaurant-dashboard__section-empty state-message state-message--empty"
          >
            Пока нет сотрудников.
          </p>

          <ul
              v-else
              class="restaurant-dashboard__staff-list"
          >
            <li
                v-for="member in staff"
                :key="member.id"
                class="restaurant-dashboard__staff-item"
            >
              <div class="restaurant-dashboard__staff-main">
                <div class="restaurant-dashboard__staff-top">
                  <span class="restaurant-dashboard__staff-name">
                    {{ member.name || 'Без имени' }}
                  </span>
                  <span class="restaurant-dashboard__staff-email">
                    {{ member.email }}
                  </span>
                </div>

                <div class="restaurant-dashboard__staff-meta">
                  <span v-if="member.phone">
                    <Phone class="ui-icon" :size="14" :stroke-width="1.9" aria-hidden="true" />
                    {{ member.phone }}
                  </span>
                </div>
              </div>

              <div class="restaurant-dashboard__staff-right">
                <select
                    v-model="member.role"
                    class="restaurant-dashboard__staff-role field-select"
                    :disabled="staffActionUserId === member.id"
                    @change="handleChangeStaffRole(member)"
                >
                  <option value="OWNER">
                    Владелец
                  </option>
                  <option value="MANAGER">
                    Менеджер
                  </option>
                  <option value="STAFF">
                    Сотрудник
                  </option>
                </select>

                <button
                    type="button"
                    class="restaurant-dashboard__order-btn restaurant-dashboard__order-btn--danger"
                    :disabled="staffActionUserId === member.id"
                    @click="handleRemoveStaff(member)"
                >
                  Удалить
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>

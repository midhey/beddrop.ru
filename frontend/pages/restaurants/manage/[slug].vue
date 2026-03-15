<script setup lang="ts">
import { computed, ref } from "vue";
import {
  ArrowLeft,
  ChevronDown,
  Clock3,
  Package,
  Phone,
  ReceiptText,
  Settings2,
  Users,
} from "lucide-vue-next";
import AddressFields from "~/components/address/AddressFields.vue";
import BaseAccordion from "~/components/ui/BaseAccordion.vue";
import { useRestaurantManageDashboardPage } from "~/composables/useRestaurantManageDashboardPage";
import placeholderImg from "~/assets/images/placeholder.png";

const {
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
} = useRestaurantManageDashboardPage();

const dashboardStats = computed(() => [
  {
    key: "orders",
    label: "Заказы",
    value: restaurantOrders.value.length,
    note: hasOrders.value ? "Есть активность" : "Пока пусто",
    icon: ReceiptText,
  },
  {
    key: "menu",
    label: "Позиции меню",
    value: products.value.length,
    note: hasProducts.value ? "Доступны к управлению" : "Нужно наполнить",
    icon: Package,
  },
  {
    key: "staff",
    label: "Сотрудники",
    value: staff.value.length,
    note: staff.value.length ? "Команда подключена" : "Команда не добавлена",
    icon: Users,
  },
]);

const expandedOrderId = ref<number | null>(null);

const handleOrderDisclosure = (orderId: number, value: boolean) => {
  expandedOrderId.value = value
    ? orderId
    : expandedOrderId.value === orderId
      ? null
      : expandedOrderId.value;
};

const getProductImageSrc = (product: (typeof menuProducts.value)[number]) => {
  const coverImage = product.images?.find((image) => image.is_cover)?.media
    ?.url;
  if (coverImage) {
    return coverImage;
  }

  const firstImage = product.images?.[0]?.media?.url;
  return firstImage || placeholderImg;
};

const roleLabels: Record<"OWNER" | "MANAGER" | "STAFF", string> = {
  OWNER: "Владелец",
  MANAGER: "Менеджер",
  STAFF: "Сотрудник",
};

const inviteLink = computed(() => {
  if (!latestStaffInvite.value) {
    return "";
  }

  if (typeof window === "undefined") {
    return `/restaurants/staff-invites/${latestStaffInvite.value.token}`;
  }

  return `${window.location.origin}/restaurants/staff-invites/${latestStaffInvite.value.token}`;
});

const copyInviteLink = async () => {
  if (
    !inviteLink.value ||
    typeof navigator === "undefined" ||
    !navigator.clipboard
  ) {
    return;
  }

  await navigator.clipboard.writeText(inviteLink.value);
};
</script>

<template>
  <section class="restaurant-dashboard page-shell">
    <div class="restaurant-dashboard__container">
      <button
        type="button"
        class="restaurant-dashboard__back page-back"
        @click="$router.back()"
      >
        <ArrowLeft
          class="ui-icon"
          :size="16"
          :stroke-width="1.9"
          aria-hidden="true"
        />
        <span>Назад</span>
      </button>

      <div class="restaurant-dashboard__header page-head">
        <div class="restaurant-dashboard__header-main">
          <span class="restaurant-dashboard__eyebrow">Кабинет ресторана</span>
          <h1 class="restaurant-dashboard__title page-title">
            Ресторан
            <span v-if="restaurant"> «{{ restaurant.name }}»</span>
          </h1>

          <div v-if="restaurant" class="restaurant-dashboard__meta">
            <span
              class="restaurant-dashboard__status status-chip"
              :class="
                restaurant.is_active
                  ? 'status-chip--success'
                  : 'status-chip--danger'
              "
            >
              {{ getRestaurantActivityLabel(restaurant.is_active) }}
            </span>

            <span
              v-if="prepTimeText"
              class="restaurant-dashboard__badge status-chip status-chip--info"
            >
              <Clock3
                class="ui-icon"
                :size="14"
                :stroke-width="1.9"
                aria-hidden="true"
              />
              {{ prepTimeText }}
            </span>

            <span
              v-if="restaurant.phone"
              class="restaurant-dashboard__badge status-chip status-chip--info"
            >
              <Phone
                class="ui-icon"
                :size="14"
                :stroke-width="1.9"
                aria-hidden="true"
              />
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

        <div v-if="restaurant" class="restaurant-dashboard__hero-stats">
          <article
            v-for="stat in dashboardStats"
            :key="stat.key"
            class="restaurant-dashboard__hero-stat"
          >
            <span class="restaurant-dashboard__hero-stat-icon">
              <component
                :is="stat.icon"
                class="ui-icon"
                :size="18"
                :stroke-width="1.9"
                aria-hidden="true"
              />
            </span>
            <div class="restaurant-dashboard__hero-stat-copy">
              <span class="restaurant-dashboard__hero-stat-label">{{
                stat.label
              }}</span>
              <strong class="restaurant-dashboard__hero-stat-value">{{
                stat.value
              }}</strong>
              <span class="restaurant-dashboard__hero-stat-note">{{
                stat.note
              }}</span>
            </div>
          </article>
        </div>
      </div>

      <!-- табы -->
      <div class="restaurant-dashboard__tabs">
        <button
          v-if="canViewOrdersTab"
          type="button"
          class="restaurant-dashboard__tab"
          :class="{
            'restaurant-dashboard__tab--active': activeTab === 'orders',
          }"
          @click="activeTab = 'orders'"
        >
          Заказы
          <span class="restaurant-dashboard__tab-count">{{
            restaurantOrders.length
          }}</span>
        </button>
        <button
          v-if="canViewMenuTab"
          type="button"
          class="restaurant-dashboard__tab"
          :class="{ 'restaurant-dashboard__tab--active': activeTab === 'menu' }"
          @click="activeTab = 'menu'"
        >
          Меню
          <span class="restaurant-dashboard__tab-count">{{
            products.length
          }}</span>
        </button>

        <button
          v-if="canViewStaffTab"
          type="button"
          class="restaurant-dashboard__tab"
          :class="{
            'restaurant-dashboard__tab--active': activeTab === 'staff',
          }"
          @click="activeTab = 'staff'"
        >
          Персонал
          <span class="restaurant-dashboard__tab-count">{{
            staff.length
          }}</span>
        </button>
        <button
          v-if="canViewSettingsTab"
          type="button"
          class="restaurant-dashboard__tab"
          :class="{
            'restaurant-dashboard__tab--active': activeTab === 'settings',
          }"
          @click="activeTab = 'settings'"
        >
          Настройки
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
      <div v-else class="restaurant-dashboard__content">
        <div
          v-if="!availableTabs.length"
          class="restaurant-dashboard__section-empty state-message state-message--empty"
        >
          Для вашей роли в этом ресторане пока нет доступных разделов.
        </div>

        <!-- ВКЛАДКА: ЗАКАЗЫ -->
        <div
          v-else-if="activeTab === 'orders' && canViewOrdersTab"
          class="restaurant-dashboard__section restaurant-dashboard__section--orders surface-card"
        >
          <div class="restaurant-dashboard__section-header section-head">
            <h2 class="restaurant-dashboard__section-title section-title">
              Заказы ресторана
            </h2>
            <span class="restaurant-dashboard__section-meta section-meta">
              {{
                hasOrders ? `${restaurantOrders.length} заказов` : "Нет заказов"
              }}
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

          <div v-else class="restaurant-dashboard__orders">
            <BaseAccordion
              v-for="order in restaurantOrders"
              :key="order.id"
              tag="article"
              class="restaurant-dashboard__order"
              :model-value="expandedOrderId === order.id"
              @update:model-value="
                (value) => handleOrderDisclosure(order.id, value)
              "
            >
              <template
                #default="{
                  open,
                  toggle,
                  triggerAttrs,
                  panelAttrs,
                  panelInnerAttrs,
                }"
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
                      {{ getOrderStatusLabel(order.status, "restaurant") }}
                    </span>
                  </div>

                  <div class="restaurant-dashboard__order-meta">
                    <span> {{ order.items_count ?? "—" }} позиций </span>
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

                  <div
                    v-bind="panelAttrs"
                    class="restaurant-dashboard__order-details-panel"
                  >
                    <div
                      v-bind="panelInnerAttrs"
                      class="restaurant-dashboard__order-details"
                    >
                      <div class="restaurant-dashboard__order-details-head">
                        <h3 class="restaurant-dashboard__order-details-title">
                          Состав заказа
                        </h3>
                        <span class="restaurant-dashboard__order-details-meta">
                          {{ order.items?.length || 0 }} позиций в заказе
                        </span>
                      </div>

                      <ul
                        v-if="order.items?.length"
                        class="restaurant-dashboard__order-items"
                      >
                        <li
                          v-for="item in order.items"
                          :key="item.id"
                          class="restaurant-dashboard__order-item"
                        >
                          <div class="restaurant-dashboard__order-item-main">
                            <strong
                              class="restaurant-dashboard__order-item-name"
                            >
                              {{ item.name_snapshot }}
                            </strong>
                            <span class="restaurant-dashboard__order-item-meta">
                              {{ item.quantity }} x
                              {{ formatPrice(item.unit_price_snapshot) }}
                            </span>
                          </div>

                          <strong
                            class="restaurant-dashboard__order-item-total"
                          >
                            {{ formatPrice(item.subtotal) }}
                          </strong>
                        </li>
                      </ul>

                      <p
                        v-else
                        class="restaurant-dashboard__order-details-empty"
                      >
                        Позиции заказа не найдены.
                      </p>
                    </div>
                  </div>
                </div>

                <div class="restaurant-dashboard__order-right">
                  <div class="restaurant-dashboard__order-total">
                    {{ formatPrice(order.total_price) }}
                  </div>

                  <button
                    v-bind="triggerAttrs"
                    class="restaurant-dashboard__order-toggle"
                    @click="toggle"
                  >
                    <span>{{
                      open ? "Скрыть состав" : "Показать состав"
                    }}</span>
                    <ChevronDown
                      class="ui-icon"
                      :class="{
                        'restaurant-dashboard__order-toggle-icon--open': open,
                      }"
                      :size="16"
                      :stroke-width="1.9"
                      aria-hidden="true"
                    />
                  </button>

                  <div class="restaurant-dashboard__order-actions">
                    <button
                      type="button"
                      class="restaurant-dashboard__order-btn"
                      :disabled="
                        !canRestaurantAcceptOrder(order) ||
                        actionOrderId === order.id
                      "
                      @click="handleAccept(order)"
                    >
                      Принять
                    </button>

                    <button
                      type="button"
                      class="restaurant-dashboard__order-btn restaurant-dashboard__order-btn--danger"
                      :disabled="
                        !canRestaurantCancelOrder(order) ||
                        actionOrderId === order.id
                      "
                      @click="handleCancel(order)"
                    >
                      Отменить
                    </button>
                  </div>
                </div>
              </template>
            </BaseAccordion>
          </div>
        </div>

        <!-- ВКЛАДКА: МЕНЮ -->
        <div
          v-else-if="activeTab === 'menu' && canViewMenuTab"
          class="restaurant-dashboard__section restaurant-dashboard__section--menu surface-card"
        >
          <div class="restaurant-dashboard__section-header section-head">
            <div>
              <h2 class="restaurant-dashboard__section-title section-title">
                Меню ресторана
              </h2>
              <span class="restaurant-dashboard__section-meta section-meta">
                {{ hasProducts ? `${products.length} позиций` : "Нет позиций" }}
              </span>
            </div>

            <button
              type="button"
              class="restaurant-dashboard__action-btn"
              @click="showCreateProductForm = !showCreateProductForm"
            >
              {{ showCreateProductForm ? "Скрыть форму" : "Добавить блюдо" }}
            </button>
          </div>

          <div v-if="hasProducts" class="restaurant-dashboard__menu-summary">
            <article class="restaurant-dashboard__menu-stat">
              <span class="restaurant-dashboard__menu-stat-label">Всего</span>
              <strong class="restaurant-dashboard__menu-stat-value">{{
                products.length
              }}</strong>
            </article>
            <article class="restaurant-dashboard__menu-stat">
              <span class="restaurant-dashboard__menu-stat-label"
                >Активных</span
              >
              <strong class="restaurant-dashboard__menu-stat-value">{{
                activeProductsCount
              }}</strong>
            </article>
            <article
              class="restaurant-dashboard__menu-stat restaurant-dashboard__menu-stat--muted"
            >
              <span class="restaurant-dashboard__menu-stat-label">Скрытых</span>
              <strong class="restaurant-dashboard__menu-stat-value">{{
                hiddenProductsCount
              }}</strong>
            </article>
          </div>

          <!-- форма добавления блюда -->
          <div
            v-if="showCreateProductForm"
            class="restaurant-dashboard__create-product surface-card--soft"
          >
            <div class="restaurant-dashboard__create-product-grid">
              <div class="restaurant-dashboard__create-product-main">
                <div class="restaurant-dashboard__form-row form-field">
                  <label class="restaurant-dashboard__form-label">
                    Название
                  </label>
                  <input
                    v-model="createForm.name"
                    type="text"
                    class="restaurant-dashboard__form-input field-input"
                    placeholder="Например, Маргарита"
                  />
                </div>

                <div class="restaurant-dashboard__create-product-columns">
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
                      placeholder="Например, 590"
                    />
                  </div>

                  <div class="restaurant-dashboard__form-row form-field">
                    <label class="restaurant-dashboard__form-label">
                      Категория
                    </label>
                    <select
                      v-model="createForm.category_id"
                      class="restaurant-dashboard__form-input field-select"
                    >
                      <option value="" disabled>
                        {{
                          categoriesLoading
                            ? "Загружаем категории..."
                            : "Выберите категорию"
                        }}
                      </option>
                      <option
                        v-for="category in productCategories"
                        :key="category.id"
                        :value="String(category.id)"
                      >
                        {{ category.name }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="restaurant-dashboard__form-row form-field">
                  <label class="restaurant-dashboard__form-label">
                    Описание
                  </label>
                  <textarea
                    v-model="createForm.description"
                    rows="3"
                    class="restaurant-dashboard__form-textarea field-textarea"
                    placeholder="Кратко опишите состав, вкус или размер порции"
                  ></textarea>
                </div>

                <div
                  class="restaurant-dashboard__form-row restaurant-dashboard__form-row--inline form-field"
                >
                  <label class="restaurant-dashboard__form-checkbox-label">
                    <input v-model="createForm.is_active" type="checkbox" />
                    <span>Сразу показывать в меню</span>
                  </label>
                </div>
              </div>

              <div class="restaurant-dashboard__create-product-aside">
                <label class="restaurant-dashboard__form-label">
                  Обложка блюда
                </label>

                <label class="restaurant-dashboard__product-upload">
                  <input
                    type="file"
                    accept="image/*"
                    class="restaurant-dashboard__product-upload-input"
                    @change="handleCreateProductImageChange"
                  />
                  <span class="restaurant-dashboard__product-upload-copy">
                    Загрузить изображение
                  </span>
                  <span class="restaurant-dashboard__product-upload-hint">
                    JPG, PNG или WebP. Первое фото станет обложкой карточки.
                  </span>
                </label>

                <div class="restaurant-dashboard__product-upload-preview">
                  <img
                    :src="createProductImagePreview || placeholderImg"
                    alt="Превью блюда"
                    class="restaurant-dashboard__product-upload-preview-image"
                  />
                </div>
              </div>
            </div>

            <div class="restaurant-dashboard__form-actions form-actions">
              <button
                type="button"
                class="restaurant-dashboard__order-btn"
                :disabled="creatingProduct || imageUploading"
                @click="handleCreateProduct"
              >
                {{
                  creatingProduct || imageUploading
                    ? "Сохраняем..."
                    : "Создать блюдо"
                }}
              </button>
            </div>
          </div>

          <p
            v-if="!hasProducts"
            class="restaurant-dashboard__section-empty state-message state-message--empty"
          >
            В меню пока нет блюд.
          </p>

          <ul v-else class="restaurant-dashboard__products">
            <li
              v-for="product in menuProducts"
              :key="product.id"
              class="restaurant-dashboard__product"
              :data-status="product.is_active ? 'active' : 'hidden'"
            >
              <div class="restaurant-dashboard__product-media">
                <img
                  :src="getProductImageSrc(product)"
                  :alt="product.name"
                  class="restaurant-dashboard__product-image"
                />
              </div>

              <div class="restaurant-dashboard__product-main">
                <div class="restaurant-dashboard__product-top">
                  <div class="restaurant-dashboard__product-heading">
                    <span class="restaurant-dashboard__product-name">
                      {{ product.name }}
                    </span>
                    <span class="restaurant-dashboard__product-id">
                      ID #{{ product.id }}
                    </span>
                  </div>
                  <span class="restaurant-dashboard__product-price">
                    {{ formatPrice(product.price) }}
                  </span>
                </div>

                <div class="restaurant-dashboard__product-meta">
                  <span class="restaurant-dashboard__product-category">
                    {{ product.category?.name || "Без категории" }}
                  </span>
                  <span class="restaurant-dashboard__product-gallery-meta">
                    {{ product.images?.length || 0 }} фото
                  </span>
                  <span
                    class="restaurant-dashboard__product-status"
                    :class="[
                      'status-chip',
                      product.is_active
                        ? 'status-chip--info'
                        : 'status-chip--danger',
                    ]"
                  >
                    {{
                      product.is_active
                        ? "Показывается в меню"
                        : "Скрыт, но сохранён"
                    }}
                  </span>
                </div>

                <p
                  v-if="product.description"
                  class="restaurant-dashboard__product-desc"
                >
                  {{ product.description }}
                </p>

                <div class="restaurant-dashboard__product-extra">
                  <span class="restaurant-dashboard__product-extra-item">
                    Обновлено: {{ formatDateTime(product.updated_at) }}
                  </span>
                  <span class="restaurant-dashboard__product-extra-item">
                    Создано: {{ formatDateTime(product.created_at) }}
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
                  {{ product.is_active ? "Скрыть из меню" : "Вернуть в меню" }}
                </button>

                <button
                  type="button"
                  class="restaurant-dashboard__order-btn restaurant-dashboard__order-btn--danger"
                  :disabled="productActionId === product.id"
                  @click="handleDeleteProduct(product)"
                >
                  Удалить блюдо
                </button>
              </div>
            </li>
          </ul>
        </div>

        <!-- ВКЛАДКА: НАСТРОЙКИ -->
        <div
          v-else-if="activeTab === 'settings' && canViewSettingsTab"
          class="restaurant-dashboard__section restaurant-dashboard__section--settings surface-card"
        >
          <div class="restaurant-dashboard__section-header section-head">
            <div>
              <h2 class="restaurant-dashboard__section-title section-title">
                Настройки ресторана
              </h2>
              <span class="restaurant-dashboard__section-meta section-meta">
                Название, описание, контакты, картинка, адрес и активность
              </span>
            </div>
          </div>

          <div class="restaurant-dashboard__settings-layout">
            <div class="restaurant-dashboard__settings-main">
              <div
                class="restaurant-dashboard__settings-card surface-card--soft"
              >
                <div class="restaurant-dashboard__settings-card-head">
                  <h3 class="restaurant-dashboard__settings-card-title">
                    Основная информация
                  </h3>
                  <p class="restaurant-dashboard__settings-card-text">
                    Эти данные используются в карточке ресторана и в клиентской
                    части каталога.
                  </p>
                </div>

                <div class="restaurant-dashboard__form-row form-field">
                  <label class="restaurant-dashboard__form-label">
                    Название ресторана
                  </label>
                  <input
                    v-model="settingsForm.name"
                    type="text"
                    class="restaurant-dashboard__form-input field-input"
                    placeholder="Например, Пицца на районе"
                  />
                </div>

                <div class="restaurant-dashboard__form-row form-field">
                  <label class="restaurant-dashboard__form-label">
                    Описание
                  </label>
                  <textarea
                    v-model="settingsForm.description"
                    rows="4"
                    class="restaurant-dashboard__form-textarea field-textarea"
                    placeholder="Опишите кухню, особенности и позиционирование ресторана"
                  ></textarea>
                </div>

                <div class="restaurant-dashboard__create-product-columns">
                  <div class="restaurant-dashboard__form-row form-field">
                    <label class="restaurant-dashboard__form-label">
                      Телефон
                    </label>
                    <input
                      v-model="settingsForm.phone"
                      type="tel"
                      class="restaurant-dashboard__form-input field-input"
                      placeholder="+7 999 123-45-67"
                    />
                  </div>

                  <div class="restaurant-dashboard__form-row form-field">
                    <label class="restaurant-dashboard__form-label">
                      Статус
                    </label>
                    <label
                      class="restaurant-dashboard__form-checkbox-label restaurant-dashboard__form-checkbox-label--boxed"
                    >
                      <input v-model="settingsForm.is_active" type="checkbox" />
                      <span>Ресторан активен и доступен клиентам</span>
                    </label>
                  </div>
                </div>

                <div class="restaurant-dashboard__create-product-columns">
                  <div class="restaurant-dashboard__form-row form-field">
                    <label class="restaurant-dashboard__form-label">
                      Время приготовления от, мин
                    </label>
                    <input
                      v-model="settingsForm.prep_time_min"
                      type="number"
                      min="0"
                      step="1"
                      class="restaurant-dashboard__form-input field-input"
                      placeholder="15"
                    />
                  </div>

                  <div class="restaurant-dashboard__form-row form-field">
                    <label class="restaurant-dashboard__form-label">
                      Время приготовления до, мин
                    </label>
                    <input
                      v-model="settingsForm.prep_time_max"
                      type="number"
                      min="0"
                      step="1"
                      class="restaurant-dashboard__form-input field-input"
                      placeholder="35"
                    />
                  </div>
                </div>
              </div>

              <div
                class="restaurant-dashboard__settings-card surface-card--soft"
              >
                <div class="restaurant-dashboard__settings-card-head">
                  <h3 class="restaurant-dashboard__settings-card-title">
                    Адрес
                  </h3>
                  <p class="restaurant-dashboard__settings-card-text">
                    Хранится отдельной сущностью и обновляется вместе с
                    рестораном, а не строкой в карточке.
                  </p>
                </div>
                <AddressFields
                  v-model="settingsForm.address"
                  line1-label="Основной адрес"
                  line1-placeholder="Улица, дом, корпус"
                  line2-label="Дополнение"
                  line2-placeholder="Этаж, вход, ориентир"
                  city-placeholder="Москва"
                  postal-code-placeholder="101000"
                  required
                />
              </div>
            </div>

            <aside class="restaurant-dashboard__settings-aside">
              <div
                class="restaurant-dashboard__settings-card surface-card--soft"
              >
                <div class="restaurant-dashboard__settings-card-head">
                  <h3 class="restaurant-dashboard__settings-card-title">
                    Изображение ресторана
                  </h3>
                  <p class="restaurant-dashboard__settings-card-text">
                    Используется как картинка карточки ресторана.
                  </p>
                </div>

                <label class="restaurant-dashboard__product-upload">
                  <input
                    :key="settingsUploadInputKey"
                    type="file"
                    accept="image/*"
                    class="restaurant-dashboard__product-upload-input"
                    @change="handleSettingsLogoChange"
                  />
                  <span class="restaurant-dashboard__product-upload-copy">
                    {{
                      hasRestaurantLogo
                        ? "Заменить изображение"
                        : "Загрузить изображение"
                    }}
                  </span>
                  <span class="restaurant-dashboard__product-upload-hint">
                    JPG, PNG или WebP. Эта картинка будет использоваться в
                    карточке ресторана.
                  </span>
                </label>

                <div
                  class="restaurant-dashboard__product-upload-preview restaurant-dashboard__product-upload-preview--landscape"
                >
                  <img
                    :src="settingsForm.logo_preview_url || placeholderImg"
                    alt="Превью ресторана"
                    class="restaurant-dashboard__product-upload-preview-image"
                  />
                </div>
              </div>

              <div
                class="restaurant-dashboard__settings-card surface-card--soft"
              >
                <div class="restaurant-dashboard__settings-card-head">
                  <h3 class="restaurant-dashboard__settings-card-title">
                    Что важно
                  </h3>
                </div>

                <ul class="restaurant-dashboard__settings-checklist">
                  <li class="restaurant-dashboard__settings-checklist-item">
                    Название и основной адрес обязательны.
                  </li>
                  <li class="restaurant-dashboard__settings-checklist-item">
                    Неактивный ресторан скрывается из каталога для клиентов.
                  </li>
                  <li class="restaurant-dashboard__settings-checklist-item">
                    Диапазон времени приготовления лучше воспринимается, чем
                    одно число.
                  </li>
                </ul>
              </div>
            </aside>
          </div>

          <div class="restaurant-dashboard__form-actions form-actions">
            <button
              type="button"
              class="restaurant-dashboard__order-btn"
              :disabled="savingSettings || imageUploading"
              @click="handleSaveSettings"
            >
              {{
                savingSettings || imageUploading
                  ? "Сохраняем..."
                  : "Сохранить настройки"
              }}
            </button>
          </div>
        </div>

        <!-- ВКЛАДКА: ПЕРСОНАЛ -->
        <div
          v-else-if="activeTab === 'staff' && canViewStaffTab"
          class="restaurant-dashboard__section restaurant-dashboard__section--staff surface-card"
        >
          <div class="restaurant-dashboard__section-header section-head">
            <div>
              <h2 class="restaurant-dashboard__section-title section-title">
                Персонал ресторана
              </h2>
              <span class="restaurant-dashboard__section-meta section-meta">
                {{
                  staff.length
                    ? `${staff.length} сотрудников`
                    : "Сотрудники не добавлены"
                }}
              </span>
            </div>
          </div>

          <div class="restaurant-dashboard__staff-summary">
            <article class="restaurant-dashboard__menu-stat">
              <span class="restaurant-dashboard__menu-stat-label"
                >Всего в команде</span
              >
              <strong class="restaurant-dashboard__menu-stat-value">{{
                staff.length
              }}</strong>
            </article>
            <article class="restaurant-dashboard__menu-stat">
              <span class="restaurant-dashboard__menu-stat-label"
                >Менеджеров</span
              >
              <strong class="restaurant-dashboard__menu-stat-value">
                {{ staff.filter((member) => member.role === "MANAGER").length }}
              </strong>
            </article>
            <article
              class="restaurant-dashboard__menu-stat restaurant-dashboard__menu-stat--muted"
            >
              <span class="restaurant-dashboard__menu-stat-label"
                >Сотрудников</span
              >
              <strong class="restaurant-dashboard__menu-stat-value">
                {{ staff.filter((member) => member.role === "STAFF").length }}
              </strong>
            </article>
          </div>

          <div class="restaurant-dashboard__create-staff surface-card--soft">
            <div class="restaurant-dashboard__create-staff-grid">
              <div class="restaurant-dashboard__create-staff-main">
                <h3 class="restaurant-dashboard__create-staff-title">
                  Пригласить в команду
                </h3>
                <p class="restaurant-dashboard__create-staff-text">
                  Создайте временную ссылку и отправьте её человеку. После входа
                  в аккаунт он сможет принять приглашение и сразу появится в
                  команде ресторана.
                </p>

                <div class="restaurant-dashboard__create-product-columns">
                  <div class="restaurant-dashboard__form-row form-field">
                    <label class="restaurant-dashboard__form-label">
                      Роль
                    </label>
                    <select
                      v-model="inviteRole"
                      class="restaurant-dashboard__form-input field-select"
                    >
                      <option value="MANAGER">Менеджер</option>
                      <option value="STAFF">Сотрудник</option>
                    </select>
                  </div>

                  <div class="restaurant-dashboard__form-row form-field">
                    <label class="restaurant-dashboard__form-label">
                      Срок действия
                    </label>
                    <select
                      v-model="inviteExpiryMinutes"
                      class="restaurant-dashboard__form-input field-select"
                    >
                      <option value="5">5 минут</option>
                      <option value="15">15 минут</option>
                      <option value="30">30 минут</option>
                      <option value="60">1 час</option>
                    </select>
                  </div>
                </div>

                <div class="restaurant-dashboard__form-actions form-actions">
                  <button
                    type="button"
                    class="restaurant-dashboard__order-btn"
                    :disabled="invitesLoading"
                    @click="handleCreateStaffInvite"
                  >
                    {{ invitesLoading ? "Генерируем..." : "Создать ссылку" }}
                  </button>
                </div>
              </div>

              <div
                v-if="latestStaffInvite"
                class="restaurant-dashboard__invite-card"
              >
                <span class="restaurant-dashboard__invite-card-label">
                  Актуальное приглашение
                </span>
                <strong class="restaurant-dashboard__invite-card-role">
                  {{ roleLabels[latestStaffInvite.role] }}
                </strong>
                <span class="restaurant-dashboard__invite-card-meta">
                  Действует до
                  {{ formatDateTime(latestStaffInvite.expires_at) }}
                </span>
                <div class="restaurant-dashboard__invite-card-link">
                  {{ inviteLink }}
                </div>
                <button
                  type="button"
                  class="restaurant-dashboard__order-btn"
                  @click="copyInviteLink"
                >
                  Копировать ссылку
                </button>
              </div>
            </div>
          </div>

          <p
            v-if="!staff.length"
            class="restaurant-dashboard__section-empty state-message state-message--empty"
          >
            Пока нет сотрудников.
          </p>

          <ul v-else class="restaurant-dashboard__staff-list">
            <li
              v-for="member in staff"
              :key="member.id"
              class="restaurant-dashboard__staff-item"
            >
              <div class="restaurant-dashboard__staff-main">
                <div class="restaurant-dashboard__staff-top">
                  <span class="restaurant-dashboard__staff-name">
                    {{ member.name || "Без имени" }}
                  </span>
                  <span
                    class="status-chip"
                    :class="
                      member.role === 'OWNER'
                        ? 'status-chip--info'
                        : member.role === 'MANAGER'
                          ? 'status-chip--success'
                          : 'status-chip--muted'
                    "
                  >
                    {{ roleLabels[member.role] }}
                  </span>
                  <span class="restaurant-dashboard__staff-email">
                    {{ member.email }}
                  </span>
                </div>

                <div class="restaurant-dashboard__staff-meta">
                  <span v-if="member.phone">
                    <Phone
                      class="ui-icon"
                      :size="14"
                      :stroke-width="1.9"
                      aria-hidden="true"
                    />
                    {{ member.phone }}
                  </span>
                </div>
              </div>

              <div class="restaurant-dashboard__staff-right">
                <select
                  v-model="member.role"
                  class="restaurant-dashboard__staff-role field-select"
                  :disabled="
                    staffActionUserId === member.id || member.role === 'OWNER'
                  "
                  @change="handleChangeStaffRole(member)"
                >
                  <option value="OWNER">Владелец</option>
                  <option value="MANAGER">Менеджер</option>
                  <option value="STAFF">Сотрудник</option>
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

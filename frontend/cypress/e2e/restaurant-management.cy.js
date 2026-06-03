const apiBaseUrl = () => Cypress.env("apiBaseUrl");
const restaurantSlug = () => Cypress.env("restaurantSlug");

const owner = {
  id: 7,
  email: "owner@mail.com",
  phone: "79990000001",
  name: "Владелец",
  is_admin: false,
  is_banned: false,
};

const manager = {
  id: 8,
  email: "manager@mail.com",
  phone: "79990000002",
  name: "Менеджер",
};

const staffMember = {
  id: 9,
  email: "staff@mail.com",
  phone: "79990000003",
  name: "Сотрудник",
};

const restaurant = {
  id: 11,
  name: "Новгородский дворик",
  description: "Домашняя кухня",
  slug: restaurantSlug(),
  phone: "+7 999 111-22-33",
  is_active: true,
  prep_time_min: 15,
  prep_time_max: 35,
  prep_time_avg_minutes: 25,
  address_id: 21,
  logo_media_id: null,
  current_user_role: "OWNER",
  created_at: "2026-05-11T08:00:00.000000Z",
  updated_at: "2026-05-11T08:00:00.000000Z",
  address: {
    id: 21,
    label: "Ресторан",
    value: "Великий Новгород, Кремль, 3",
    unrestricted_value: "Великий Новгород, Кремль, 3",
    line1: "Великий Новгород, Кремль, 3",
    line2: null,
    city: "Великий Новгород",
    postal_code: null,
    lat: 58.521475,
    lng: 31.275475,
    flat: null,
    entrance: null,
    floor: null,
    intercom: null,
  },
  logo: null,
};

const categories = [
  { id: 101, slug: "pizza", name: "Пицца", sort_order: 1 },
  { id: 102, slug: "soups", name: "Супы", sort_order: 2 },
];

const product = {
  id: 501,
  restaurant_id: restaurant.id,
  category_id: categories[0].id,
  category: categories[0],
  name: "Маргарита",
  description: "Томаты, моцарелла, базилик",
  price: "590.00",
  is_active: true,
  created_at: "2026-05-11T08:00:00.000000Z",
  updated_at: "2026-05-11T08:00:00.000000Z",
  images: [
    {
      id: 801,
      sort_order: 0,
      is_cover: true,
      media: {
        id: 701,
        url: "/placeholder.png",
        mime: "image/png",
        size_bytes: 128,
      },
    },
    {
      id: 802,
      sort_order: 1,
      is_cover: false,
      media: {
        id: 702,
        url: "/placeholder.png",
        mime: "image/png",
        size_bytes: 128,
      },
    },
  ],
};

const acceptedOrder = {
  id: 9001,
  status: "ACCEPTED_BY_RESTAURANT",
  payment_status: "PENDING",
  payment_method: "CASH",
  total_price: "590.00",
  courier_fee: "0.00",
  comment: "Без лука",
  items_count: 1,
  courier_id: null,
  created_at: "2026-05-11T09:00:00.000000Z",
  updated_at: "2026-05-11T09:00:00.000000Z",
  restaurant,
  delivery_address: {
    id: 301,
    value: "Великий Новгород, Софийская площадь, 1",
    line1: "Великий Новгород, Софийская площадь, 1",
    lat: 58.522,
    lng: 31.277,
  },
  items: [
    {
      id: 1,
      product_id: product.id,
      name_snapshot: product.name,
      unit_price_snapshot: "590.00",
      quantity: 1,
      subtotal: "590.00",
    },
  ],
  route_segments: [],
};

const paginated = (items) => ({
  data: items,
  links: {
    first: null,
    last: null,
    prev: null,
    next: null,
  },
  meta: {
    current_page: 1,
    from: items.length ? 1 : null,
    last_page: 1,
    path: `${apiBaseUrl()}/mock`,
    per_page: 20,
    to: items.length,
    total: items.length,
  },
});

const imageFile = (index) => ({
  contents: Cypress.Buffer.from(`image-${index}`),
  fileName: `dish-${index}.png`,
  mimeType: "image/png",
  lastModified: Date.now(),
});

const mockAuth = () => {
  cy.intercept("POST", `${apiBaseUrl()}/auth/refresh`, {
    access_token: "test-token",
    token_type: "bearer",
  }).as("refresh");

  cy.intercept("GET", `${apiBaseUrl()}/me/bootstrap`, {
    user: owner,
    has_restaurants_access: true,
    has_courier_access: false,
    active_order: null,
    cart_summary: null,
  }).as("bootstrap");
};

const mockRestaurantDashboard = () => {
  cy.intercept("GET", `${apiBaseUrl()}/restaurants/${restaurantSlug()}`, {
    restaurant,
  }).as("restaurant");

  cy.intercept("GET", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/products*`, paginated([product])).as("products");
  cy.intercept("GET", `${apiBaseUrl()}/product-categories`, { categories }).as("categories");
  cy.intercept("GET", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/orders*`, paginated([acceptedOrder])).as("orders");
  cy.intercept("GET", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/users`, {
    staff: [
      {
        id: owner.id,
        email: owner.email,
        phone: owner.phone,
        name: owner.name,
        role: "OWNER",
      },
      {
        id: manager.id,
        email: manager.email,
        phone: manager.phone,
        name: manager.name,
        role: "MANAGER",
      },
      {
        id: staffMember.id,
        email: staffMember.email,
        phone: staffMember.phone,
        name: staffMember.name,
        role: "STAFF",
      },
    ],
  }).as("staff");
};

const visitDashboard = (options = {}) => {
  mockAuth();
  mockRestaurantDashboard();

  cy.visit(`/restaurants/manage/${restaurantSlug()}`, options);
  cy.location("pathname", { timeout: 10000 }).should("eq", `/restaurants/manage/${restaurantSlug()}`);
  cy.wait(["@refresh", "@bootstrap", "@restaurant", "@products", "@categories", "@orders", "@staff"]);
};

describe("restaurant management dashboard", () => {
  beforeEach(() => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
  });

  it("creates a product with up to five photos", () => {
    visitDashboard();

    cy.contains("button", "Меню").click();
    cy.contains("button", "Добавить блюдо").click();

    cy.contains(".restaurant-dashboard__product-upload-copy", "Загрузить до 5 фото").should("be.visible");
    cy.get(".restaurant-dashboard__create-product input[type=file]").selectFile(
      [1, 2, 3, 4, 5].map(imageFile),
      { force: true },
    );
    cy.get(".restaurant-dashboard__create-product .restaurant-dashboard__product-upload-preview-image")
      .should("have.length", 5);

    cy.contains(".restaurant-dashboard__form-label", "Название")
      .parent()
      .find("input")
      .clear()
      .type("Пепперони");
    cy.contains(".restaurant-dashboard__form-label", "Цена")
      .parent()
      .find("input")
      .clear()
      .type("690");
    cy.contains(".restaurant-dashboard__form-label", "Категория")
      .parent()
      .find("select")
      .select("Пицца");
    cy.contains(".restaurant-dashboard__form-label", "Описание")
      .parent()
      .find("textarea")
      .type("Острая пицца");

    cy.intercept("POST", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/products`, {
      statusCode: 201,
      body: {
        data: {
          ...product,
          id: 777,
          name: "Пепперони",
          description: "Острая пицца",
          price: "690.00",
          images: [],
        },
      },
    }).as("createProduct");

    let mediaId = 900;
    cy.intercept("POST", `${apiBaseUrl()}/media`, (req) => {
      mediaId += 1;
      req.reply({
        statusCode: 201,
        body: {
          media: {
            id: mediaId,
            disk: "public",
            path: `media/${mediaId}.png`,
            mime: "image/png",
            size_bytes: 128,
            url: `/storage/media/${mediaId}.png`,
            created_at: "2026-05-11T09:00:00.000000Z",
            updated_at: "2026-05-11T09:00:00.000000Z",
          },
        },
      });
    }).as("uploadMedia");

    cy.intercept("POST", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/products/777/images`, (req) => {
      req.reply({
        statusCode: 201,
        body: {
          data: {
            id: 1000 + req.body.sort_order,
            sort_order: req.body.sort_order,
            is_cover: req.body.is_cover,
            media: {
              id: req.body.media_id,
              url: `/storage/media/${req.body.media_id}.png`,
              mime: "image/png",
              size_bytes: 128,
            },
          },
        },
      });
    }).as("addImage");

    cy.contains("button", "Создать блюдо").click();
    cy.wait("@createProduct").its("request.body").should("include", {
      name: "Пепперони",
      category_id: categories[0].id,
      is_active: true,
    });
    cy.wait("@uploadMedia");
    cy.wait("@uploadMedia");
    cy.wait("@uploadMedia");
    cy.wait("@uploadMedia");
    cy.wait("@uploadMedia");
    cy.wait("@addImage").its("request.body").should("include", { is_cover: true, sort_order: 0 });
    cy.wait("@addImage").its("request.body").should("include", { is_cover: false, sort_order: 1 });
    cy.wait("@addImage");
    cy.wait("@addImage");
    cy.wait("@addImage");
    cy.contains("Блюдо добавлено").should("be.visible");
    cy.contains(".restaurant-dashboard__product-name", "Пепперони").should("be.visible");
  });

  it("edits an existing product and explains the next order step", () => {
    visitDashboard();

    cy.contains(".restaurant-dashboard__order-next-step", "отметьте готовность к выдаче").should("be.visible");

    cy.contains("button", "Меню").click();
    cy.contains(".restaurant-dashboard__product", "Маргарита")
      .contains("button", "Редактировать")
      .click();

    cy.contains(".restaurant-dashboard__product-edit", "Фото блюда").should("be.visible");
    cy.contains(".restaurant-dashboard__product-gallery-count", "2 / 5").should("be.visible");
    cy.contains(".restaurant-dashboard__product-edit .restaurant-dashboard__form-label", "Название")
      .parent()
      .find("input")
      .clear()
      .type("Маргарита XL");
    cy.contains(".restaurant-dashboard__product-edit .restaurant-dashboard__form-label", "Цена")
      .parent()
      .find("input")
      .clear()
      .type("790");
    cy.contains(".restaurant-dashboard__product-edit .restaurant-dashboard__form-label", "Категория")
      .parent()
      .find("select")
      .select("Супы");

    cy.intercept("PUT", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/products/${product.id}`, {
      data: {
        ...product,
        name: "Маргарита XL",
        price: "790.00",
        category_id: categories[1].id,
        category: categories[1],
      },
    }).as("updateProduct");

    cy.contains(".restaurant-dashboard__product-edit button", "Сохранить").click();
    cy.wait("@updateProduct").its("request.body").should("include", {
      name: "Маргарита XL",
      price: 790,
      category_id: categories[1].id,
    });
    cy.contains("Блюдо обновлено").should("be.visible");
    cy.contains(".restaurant-dashboard__product-name", "Маргарита XL").should("be.visible");
  });

  it("promotes the next image when deleting the current cover", () => {
    visitDashboard();

    cy.contains("button", "Меню").click();
    cy.contains(".restaurant-dashboard__product", "Маргарита")
      .contains("button", "Редактировать")
      .click();

    cy.contains(".restaurant-dashboard__product-gallery-count", "2 / 5").should("be.visible");
    cy.get(".restaurant-dashboard__product-gallery-cover").should("have.length", 1);

    cy.intercept("DELETE", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/products/${product.id}/images/801`, {
      statusCode: 204,
      body: {},
    }).as("deleteCover");

    cy.intercept("PUT", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/products/${product.id}/images/802`, (req) => {
      req.reply({
        data: {
          ...product.images[1],
          is_cover: req.body.is_cover,
        },
      });
    }).as("promoteCover");

    cy.get(".restaurant-dashboard__product-gallery-item")
      .first()
      .contains("button", "Удалить")
      .click();

    cy.wait("@deleteCover");
    cy.wait("@promoteCover").its("request.body").should("deep.equal", {
      is_cover: true,
    });

    cy.contains(".restaurant-dashboard__product-gallery-count", "1 / 5").should("be.visible");
    cy.get(".restaurant-dashboard__product-gallery-cover").should("have.length", 1);
  });

  it("initializes the settings map when the restaurant already has coordinates", () => {
    visitDashboard({
      onBeforeLoad(win) {
        win.__mapConstructed = 0;
        win.maplibregl = {
          Map: class {
            constructor() {
              win.__mapConstructed += 1;
            }

            on() {}
            setCenter() {}
            resize() {}
            remove() {}
          },
          Marker: class {
            setLngLat() {
              return this;
            }

            addTo() {
              return this;
            }

            on() {}
          },
        };
      },
    });

    cy.contains("button", "Настройки").click();
    cy.contains("h2", "Настройки ресторана").should("be.visible");
    cy.get(".address-picker__map").should("be.visible");
    cy.window().its("__mapConstructed").should("be.greaterThan", 0);
  });

  it("does not offer owner as an editable staff role or mutate before update succeeds", () => {
    visitDashboard();

    cy.contains("button", "Персонал").click();

    cy.contains(".restaurant-dashboard__staff-item", manager.email)
      .find(".restaurant-dashboard__staff-role")
      .within(() => {
        cy.get("option[value=OWNER]").should("not.exist");
      });

    cy.contains(".restaurant-dashboard__staff-item", staffMember.email)
      .as("staffRow")
      .find(".restaurant-dashboard__staff-role")
      .as("staffRoleSelect")
      .should("have.value", "STAFF");

    cy.intercept("PUT", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/users/${staffMember.id}`, {
      delay: 300,
      body: {
        message: "Роль сотрудника обновлена",
      },
    }).as("updateStaffRole");

    cy.get("@staffRoleSelect").select("MANAGER");
    cy.get("@staffRoleSelect").should("have.value", "STAFF");
    cy.get("@staffRow").contains(".status-chip", "Сотрудник").should("be.visible");

    cy.wait("@updateStaffRole").its("request.body").should("deep.equal", {
      role: "MANAGER",
    });
    cy.get("@staffRoleSelect").should("have.value", "MANAGER");
    cy.get("@staffRow").contains(".status-chip", "Менеджер").should("be.visible");
  });
});

export {};

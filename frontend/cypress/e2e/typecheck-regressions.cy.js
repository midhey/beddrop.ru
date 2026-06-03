const apiBaseUrl = () => Cypress.env("apiBaseUrl");

const admin = {
  id: 1,
  email: "admin@mail.com",
  phone: "79990000000",
  name: "Администратор",
  is_admin: true,
  is_banned: false,
};

const user = {
  id: 4,
  email: "customer@mail.com",
  phone: "79990000004",
  name: "Покупатель",
  is_admin: false,
  is_banned: false,
};

const paginated = (items, total = items.length) => ({
  data: items,
  links: { first: null, last: null, prev: null, next: null },
  meta: {
    current_page: 1,
    from: items.length ? 1 : null,
    last_page: 1,
    path: `${apiBaseUrl()}/mock`,
    per_page: 20,
    to: items.length,
    total,
  },
});

const mockAuth = (currentUser, shell = {}) => {
  cy.intercept("POST", `${apiBaseUrl()}/auth/refresh`, {
    access_token: "test-token",
    token_type: "bearer",
  }).as("refresh");

  cy.intercept("GET", `${apiBaseUrl()}/me/bootstrap`, {
    user: currentUser,
    has_restaurants_access: Boolean(shell.hasRestaurantsAccess),
    has_courier_access: Boolean(shell.hasCourierAccess),
    active_order: null,
    cart_summary: null,
  }).as("bootstrap");
};

describe("typecheck regression coverage", () => {
  beforeEach(() => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
  });

  it("keeps the order details page stable when delivery time has no comma-separated time part", () => {
    mockAuth(user);

    cy.intercept("GET", `${apiBaseUrl()}/orders/42`, {
      data: {
        id: 42,
        status: "COURIER_ASSIGNED",
        payment_status: "PAID",
        payment_method: "ONLINE",
        total_price: "1200.00",
        courier_fee: "150.00",
        comment: null,
        delivery_address_id: 101,
        delivery_lat: 58.521,
        delivery_lng: 31.275,
        estimated_delivery_at: "not-a-date",
        delivery_distance_meters: 3200,
        delivery_duration_seconds: 900,
        logistics_snapshot: {
          time: { prep: 20, delivery: 15, total: 35 },
        },
        restaurant: {
          id: 11,
          name: "Новгородский дворик",
          slug: "novgorodskii-dvorik",
          description: null,
          phone: null,
          is_active: true,
          accepts_orders: true,
          timezone: "Europe/Moscow",
          opens_at: null,
          closes_at: null,
          closed_reason: null,
          availability: {
            is_open: true,
            accepts_orders: true,
            timezone: "Europe/Moscow",
            opens_at: null,
            closes_at: null,
            closed_reason: null,
            status: "open",
          },
          prep_time_min: null,
          prep_time_max: null,
          prep_time_avg_minutes: null,
          address_id: null,
          created_at: "2026-05-11T08:00:00.000000Z",
          updated_at: "2026-05-11T08:00:00.000000Z",
        },
        delivery_address: null,
        items: [
          {
            id: 1,
            product_id: 501,
            name_snapshot: "Обед",
            unit_price_snapshot: "1200.00",
            quantity: 1,
            subtotal: 1200,
            product: {
              id: 501,
              restaurant_id: 11,
              category_id: null,
              name: "Обед",
              description: null,
              price: "1200.00",
              is_active: true,
              created_at: "2026-05-11T08:00:00.000000Z",
              updated_at: "2026-05-11T08:00:00.000000Z",
              images: [],
            },
          },
        ],
        events: [],
        route_segments: [
          {
            id: 71,
            order_id: 42,
            segment_type: "restaurant_to_client",
            mode: "auto",
            distance_meters: 3200,
            duration_seconds: 900,
            encoded_shape: "_p~iF~ps|U_ulLnnqC_mqNvxq`@",
          },
        ],
        items_count: 1,
        created_at: "2026-05-11T09:00:00.000000Z",
        updated_at: "2026-05-11T09:00:00.000000Z",
      },
    }).as("order");

    cy.visit("/orders/42", {
      onBeforeLoad(win) {
        win.maplibregl = {
          Map: class {
            on(event, callback) {
              if (event === "load") callback();
            }

            getLayer() {
              return false;
            }

            getSource() {
              return false;
            }

            addSource() {}
            addLayer() {}
            removeLayer() {}
            removeSource() {}
            fitBounds() {}
            remove() {}
          },
          Marker: class {
            setLngLat() {
              return this;
            }

            addTo() {
              return this;
            }

            remove() {}
          },
          LngLatBounds: class {
            constructor() {
              this.empty = true;
            }

            extend() {
              this.empty = false;
            }

            isEmpty() {
              return this.empty;
            }
          },
        };
      },
    });
    cy.wait(["@refresh", "@bootstrap", "@order"]);
    cy.contains("h1", "Заказ #42").should("be.visible");
    cy.contains(".order-page__delivery-time", "not-a-date").should("be.visible");
    cy.contains(".order-item", "Обед").should("be.visible");
  });

  it("renders admin pagination totals on users, couriers, and restaurants pages", () => {
    mockAuth(admin);

    cy.intercept("GET", `${apiBaseUrl()}/admin/users*`, paginated([
      {
        ...user,
        orders_count: 3,
        restaurants_count: 0,
        created_at: "2026-05-11T08:00:00.000000Z",
        updated_at: "2026-05-11T08:00:00.000000Z",
      },
    ], 23)).as("users");

    cy.intercept("GET", `${apiBaseUrl()}/admin/couriers*`, paginated([
      {
        user_id: 17,
        status: "ACTIVE",
        vehicle: "BIKE",
        rating: 5,
        user: {
          id: 17,
          email: "courier@mail.com",
          phone: "79990000017",
          name: "Курьер",
          is_admin: false,
          is_banned: false,
          created_at: "2026-05-11T08:00:00.000000Z",
          updated_at: "2026-05-11T08:00:00.000000Z",
        },
        orders_count: 9,
        latest_location: null,
        open_shift: null,
        created_at: "2026-05-11T08:00:00.000000Z",
        updated_at: "2026-05-11T08:00:00.000000Z",
      },
    ], 7)).as("couriers");

    cy.intercept("GET", `${apiBaseUrl()}/admin/restaurants*`, paginated([
      {
        id: 11,
        name: "Новгородский дворик",
        description: null,
        slug: "novgorodskii-dvorik",
        phone: null,
        is_active: true,
        accepts_orders: true,
        timezone: "Europe/Moscow",
        opens_at: null,
        closes_at: null,
        closed_reason: null,
        availability: {
          is_open: true,
          accepts_orders: true,
          timezone: "Europe/Moscow",
          opens_at: null,
          closes_at: null,
          closed_reason: null,
          status: "open",
        },
        prep_time_min: null,
        prep_time_max: null,
        prep_time_avg_minutes: null,
        address_id: null,
        current_user_role: null,
        created_at: "2026-05-11T08:00:00.000000Z",
        updated_at: "2026-05-11T08:00:00.000000Z",
      },
    ], 12)).as("restaurants");

    cy.visit("/admin/users");
    cy.wait(["@refresh", "@bootstrap", "@users"]);
    cy.contains(".section-meta", "Всего:").contains("23").should("be.visible");

    cy.visit("/admin/couriers");
    cy.wait("@couriers");
    cy.contains(".section-meta", "Всего:").contains("7").should("be.visible");

    cy.visit("/admin/restaurants");
    cy.wait("@restaurants");
    cy.contains(".section-meta", "Всего:").contains("12").should("be.visible");
  });
});

export {};

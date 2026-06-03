const apiBaseUrl = () => Cypress.env("apiBaseUrl");

const courier = {
  id: 17,
  email: "courier@mail.com",
  phone: "79990000017",
  name: "Курьер",
  is_admin: false,
  is_banned: false,
};

const paginated = (items) => ({
  data: items,
  links: { first: null, last: null, prev: null, next: null },
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

const mockAuth = () => {
  cy.intercept("POST", `${apiBaseUrl()}/auth/refresh`, {
    access_token: "test-token",
    token_type: "bearer",
  }).as("refresh");

  cy.intercept("GET", `${apiBaseUrl()}/me/bootstrap`, {
    user: courier,
    has_restaurants_access: false,
    has_courier_access: true,
    active_order: null,
    cart_summary: null,
  }).as("bootstrap");
};

const mockCourierDashboard = () => {
  cy.intercept("GET", `${apiBaseUrl()}/courier/profile`, {
    profile: {
      user_id: courier.id,
      status: "ACTIVE",
      vehicle: "BIKE",
      rating: 5,
    },
  }).as("profile");

  cy.intercept("GET", `${apiBaseUrl()}/courier/shifts/current`, {
    shift: null,
  }).as("shift");

  cy.intercept("GET", `${apiBaseUrl()}/courier/orders/history`, paginated([])).as("history");
  cy.intercept("GET", `${apiBaseUrl()}/courier/orders/available`, {
    statusCode: 422,
    body: { message: "У вас нет открытой смены." },
  }).as("available");
  cy.intercept("GET", `${apiBaseUrl()}/courier/orders/active`, paginated([])).as("active");
  cy.intercept("GET", `${apiBaseUrl()}/courier/earnings`, {
    today: { deliveries_count: 2, earnings_sum: "300.00", total_orders_sum: "1400.00" },
    week: { deliveries_count: 5, earnings_sum: "760.00", total_orders_sum: "3900.00" },
    total: { deliveries_count: 12, earnings_sum: "1800.00", total_orders_sum: "9900.00" },
  }).as("earnings");
};

const activeOrder = {
  id: 501,
  status: "COURIER_ASSIGNED",
  payment_status: "PAID",
  payment_method: "ONLINE",
  total_price: "1400.00",
  courier_fee: "300.00",
  comment: null,
  delivery_address_id: 301,
  delivery_lat: 58.522,
  delivery_lng: 31.277,
  delivery_price_snapshot: "250.00",
  delivery_distance_meters: 1200,
  delivery_duration_seconds: 600,
  courier_estimated_fee: "300.00",
  restaurant: {
    id: 11,
    name: "Новгородский дворик",
    address: {
      id: 21,
      line1: "Великий Новгород, Кремль, 3",
      city: "Великий Новгород",
      line2: null,
      postal_code: null,
      lat: "58.521475",
      lng: "31.275475",
    },
  },
  delivery_address: {
    id: 301,
    value: "Великий Новгород, Софийская площадь, 1",
    line1: "Великий Новгород, Софийская площадь, 1",
    city: "Великий Новгород",
    line2: null,
    postal_code: null,
    lat: "58.522",
    lng: "31.277",
  },
  items_count: 1,
  route_segments: [
    {
      id: 71,
      order_id: 501,
      segment_type: "courier_to_restaurant",
      mode: "auto",
      distance_meters: 900,
      duration_seconds: 420,
      encoded_shape: "_p~iF~ps|U_ulLnnqC_mqNvxq`@",
    },
  ],
  created_at: "2026-05-11T09:00:00.000000Z",
  updated_at: "2026-05-11T09:00:00.000000Z",
};

describe("courier dashboard", () => {
  beforeEach(() => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
    mockAuth();
    mockCourierDashboard();
  });

  it("shows earnings and withdraw placeholder", () => {
    cy.visit("/courier");
    cy.wait(["@refresh", "@bootstrap", "@profile", "@shift", "@history", "@available", "@earnings"]);

    cy.contains("h2", "Заработок").should("be.visible");
    cy.contains("Сегодня").should("be.visible");
    cy.contains("300 ₽").should("be.visible");
    cy.contains("2 доставок").should("be.visible");
    cy.contains("Оборот 1 400 ₽").should("be.visible");

    cy.contains("button", "Вывести деньги").click();
    cy.contains("Пока не работает").should("be.visible");
  });

  it("renders an active order map with string coordinates from API payloads", () => {
    cy.intercept("GET", `${apiBaseUrl()}/courier/shifts/current`, {
      shift: {
        id: 33,
        courier_user_id: courier.id,
        status: "OPEN",
        started_at: "2026-05-11T08:00:00.000000Z",
        ended_at: null,
      },
    }).as("openShift");

    cy.intercept("GET", `${apiBaseUrl()}/courier/orders/available`, paginated([])).as("availableOpen");
    cy.intercept("GET", `${apiBaseUrl()}/courier/orders/active`, paginated([activeOrder])).as("activeWithRoute");

    cy.visit("/courier", {
      onBeforeLoad(win) {
        win.__mapConstructed = 0;
        win.maplibregl = {
          Map: class {
            constructor() {
              win.__mapConstructed += 1;
            }

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

    cy.wait(["@refresh", "@bootstrap", "@profile", "@openShift", "@availableOpen", "@activeWithRoute"]);
    cy.contains(".courier-order", "Заказ #501").should("be.visible");
    cy.get(".route-map").should("be.visible");
    cy.window().its("__mapConstructed").should("be.greaterThan", 0);
  });
});

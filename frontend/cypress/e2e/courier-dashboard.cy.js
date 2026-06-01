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
});

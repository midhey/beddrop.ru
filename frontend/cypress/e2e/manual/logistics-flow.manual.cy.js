const apiBaseUrl = () => Cypress.env("apiBaseUrl");
const restaurantSlug = () => Cypress.env("restaurantSlug");

const account = (name) => {
  const selected = Cypress.env("accounts")?.[name];

  if (!selected?.email || !selected?.password) {
    throw new Error(`Missing Cypress account credentials for [${name}]. Check cypress config.`);
  }

  return selected;
};

const loginByApi = (credentials) => {
  return cy.request({
    method: "POST",
    url: `${apiBaseUrl()}/auth/login`,
    body: {
      email: credentials.email,
      password: credentials.password,
      client_type: "web",
    },
  });
};

const authorize = (credentials) => {
  return loginByApi(credentials).then((response) => response.body.access_token);
};

const authHeaders = (accessToken) => ({
  Authorization: `Bearer ${accessToken}`,
});

const manualDeliveryAddress = {
  label: "manual e2e адрес",
  value: "г Великий Новгород, пр-кт Мира, д 3",
  unrestricted_value: "173025, Новгородская обл, г Великий Новгород, пр-кт Мира, д 3",
  line1: "пр-кт Мира, д 3",
  city: "Великий Новгород",
  postal_code: "173025",
  lat: 58.544044,
  lng: 31.237638,
  qc_geo: 0,
  geo_source: "manual_cypress",
};

const createDeliveryAddress = (accessToken) => {
  return cy
    .request({
      method: "POST",
      url: `${apiBaseUrl()}/addresses`,
      headers: authHeaders(accessToken),
      body: manualDeliveryAddress,
    })
    .then((response) => {
      expect(response.status).to.eq(201);
      expect(response.body.address.id).to.be.a("number");

      return response.body.address;
    });
};

const fetchRestaurant = () => {
  return cy
    .request(`${apiBaseUrl()}/restaurants/${restaurantSlug()}`)
    .then((response) => {
      expect(response.status).to.eq(200);
      expect(response.body.restaurant.id).to.be.a("number");
      expect(response.body.restaurant.address.lat).to.be.a("number");
      expect(response.body.restaurant.address.lng).to.be.a("number");

      return response.body.restaurant;
    });
};

const fetchFirstProduct = () => {
  return cy
    .request(`${apiBaseUrl()}/restaurants/${restaurantSlug()}/products?per_page=1`)
    .then((response) => {
      expect(response.status).to.eq(200);
      expect(response.body.data).to.have.length.greaterThan(0);

      return response.body.data[0];
    });
};

const resetCart = (accessToken) => {
  return cy.request({
    method: "DELETE",
    url: `${apiBaseUrl()}/cart`,
    headers: authHeaders(accessToken),
    failOnStatusCode: false,
  });
};

const addProductToCart = (accessToken, productId) => {
  return cy.request({
    method: "POST",
    url: `${apiBaseUrl()}/cart/items`,
    headers: authHeaders(accessToken),
    body: {
      product_id: productId,
      quantity: 1,
    },
  });
};

describe("manual logistics flow", () => {
  beforeEach(() => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
  });

  it("opens admin logistics and checks DaData plus Valhalla debug tools", () => {
    authorize(account("admin"));

    cy.intercept("GET", `${apiBaseUrl()}/admin/logistics/settings`).as("settings");
    cy.visit("/admin/logistics");
    cy.location("pathname", { timeout: 10000 }).should("eq", "/admin/logistics");
    cy.wait("@settings").its("response.statusCode").should("eq", 200);

    cy.contains("h1", "Логистика").should("be.visible");
    cy.contains("Настройки доставки").should("be.visible");
    cy.contains("DaData").should("be.visible");
    cy.contains("Valhalla").should("be.visible");

    cy.intercept("POST", `${apiBaseUrl()}/admin/logistics/test-address`).as("testAddress");
    cy.contains("button", "Проверить").click();
    cy.wait("@testAddress").then(({ response }) => {
      expect(response.statusCode).to.eq(200);
      expect(response.body.address.data.lat).to.be.a("number");
      expect(response.body.address.data.lng).to.be.a("number");
    });
    cy.get(".admin-logistics__json").contains("Великий Новгород").should("be.visible");

    cy.intercept("POST", `${apiBaseUrl()}/admin/logistics/test-route`).as("testRoute");
    cy.contains("button", "Построить").click();
    cy.wait("@testRoute").then(({ response }) => {
      expect(response.statusCode).to.eq(200);
      expect(response.body.route.distance_meters).to.be.greaterThan(0);
      expect(response.body.route.duration_seconds).to.be.greaterThan(0);
      expect(response.body.route.encoded_shape).to.be.a("string").and.not.be.empty;
    });
    cy.get(".route-map__canvas", { timeout: 15000 }).should("be.visible");
  });

  it("calculates checkout delivery, draws the route, creates an order, and shows route segments", () => {
    let createdOrderId;

    authorize(account("user")).then((accessToken) => {
      resetCart(accessToken);
      createDeliveryAddress(accessToken);
      fetchRestaurant();
      fetchFirstProduct().then((product) => {
        addProductToCart(accessToken, product.id).its("status").should("eq", 201);
      });
    });

    cy.intercept("POST", `${apiBaseUrl()}/delivery/quote`).as("deliveryQuote");
    cy.visit("/orders/create");
    cy.location("pathname", { timeout: 10000 }).should("eq", "/orders/create");

    cy.wait("@deliveryQuote", { timeout: 20000 }).then(({ response }) => {
      expect(response.statusCode).to.eq(200);
      expect(response.body.quote.distance_meters).to.be.greaterThan(0);
      expect(response.body.quote.duration_seconds).to.be.greaterThan(0);
      expect(response.body.quote.delivery_price).to.be.greaterThan(0);
      expect(response.body.quote.price.service).to.be.greaterThan(0);
      expect(response.body.quote.route.encoded_shape).to.be.a("string").and.not.be.empty;
    });

    cy.contains("h1", "Оформление заказа").should("be.visible");
    cy.contains("Маршрут доставки").should("be.visible");
    cy.contains("Сервисный сбор").should("be.visible");
    cy.contains("Доставка").should("be.visible");
    cy.contains(/км/).should("be.visible");
    cy.contains(/мин/).should("be.visible");
    cy.get(".route-map__canvas", { timeout: 15000 }).should("be.visible");

    cy.intercept("POST", `${apiBaseUrl()}/orders`).as("createOrder");
    cy.contains("button", "Оформить заказ").should("not.be.disabled").click();
    cy.wait("@createOrder", { timeout: 20000 }).then(({ response }) => {
      expect(response.statusCode).to.eq(201);
      expect(response.body.data.id).to.be.a("number");
      expect(response.body.data.route_segments.length).to.be.greaterThan(0);
      createdOrderId = response.body.data.id;
    });

    cy.then(() => {
      cy.visit(`/orders/${createdOrderId}`);
    });

    cy.contains("Заказ").should("be.visible");
    cy.contains("Маршрут").should("be.visible");
    cy.get(".route-map__canvas", { timeout: 15000 }).should("be.visible");
  });
});

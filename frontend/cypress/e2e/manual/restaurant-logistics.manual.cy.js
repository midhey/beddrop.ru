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
  return loginByApi(credentials).then((response) => {
    expect(response.status).to.eq(200);

    return response.body.access_token;
  });
};

describe("manual restaurant logistics settings", () => {
  beforeEach(() => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
  });

  it("lets owner save a DaData restaurant address and prep-time ETA settings", () => {
    authorize(account("restaurantOwner"));

    cy.intercept("GET", `${apiBaseUrl()}/restaurants/${restaurantSlug()}`).as("restaurant");
    cy.intercept("GET", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/products*`).as("products");
    cy.intercept("GET", `${apiBaseUrl()}/product-categories`).as("categories");
    cy.intercept("GET", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/orders*`).as("orders");
    cy.intercept("GET", `${apiBaseUrl()}/restaurants/${restaurantSlug()}/users`).as("staff");
    cy.visit(`/restaurants/manage/${restaurantSlug()}`);
    cy.location("pathname", { timeout: 10000 }).should("eq", `/restaurants/manage/${restaurantSlug()}`);
    cy.wait("@restaurant").its("response.statusCode").should("eq", 200);
    cy.wait(["@products", "@categories", "@orders", "@staff"], { timeout: 20000 });

    cy.contains("button", "Настройки").click();
    cy.contains("h2", "Настройки ресторана").should("be.visible");

    cy.intercept("GET", `${apiBaseUrl()}/geo/address-suggestions*`).as("addressSuggestions");

    cy.contains(".form-field__label", "Адрес ресторана")
      .parents(".address-picker__search")
      .find("input")
      .should("be.enabled")
      .clear()
      .type("Великий Новгород, Большая Московская 10");

    cy.wait("@addressSuggestions", { timeout: 20000 }).then(({ response }) => {
      expect(response.statusCode).to.eq(200);
      expect(response.body.suggestions.length).to.be.greaterThan(0);
      expect(response.body.suggestions[0].data.lat).to.be.a("number");
      expect(response.body.suggestions[0].data.lng).to.be.a("number");
    });

    cy.get(".address-picker__suggestion").first().click();

    cy.contains(".restaurant-dashboard__form-label", "Минимум готовки, мин")
      .parent()
      .find("input")
      .clear()
      .type("18");

    cy.contains(".restaurant-dashboard__form-label", "Максимум готовки, мин")
      .parent()
      .find("input")
      .clear()
      .type("42");

    cy.contains(".restaurant-dashboard__eta-preview", "Среднее время для ETA")
      .contains("30 мин")
      .should("be.visible");

    cy.intercept("PUT", `${apiBaseUrl()}/restaurants/*`).as("updateRestaurant");
    cy.contains("button", "Сохранить настройки").click();

    cy.wait("@updateRestaurant", { timeout: 20000 }).then(({ response }) => {
      expect(response.statusCode).to.eq(200);
      expect(response.body.restaurant.prep_time_min).to.eq(18);
      expect(response.body.restaurant.prep_time_max).to.eq(42);
      expect(response.body.restaurant.prep_time_avg_minutes).to.eq(30);
      expect(response.body.restaurant.address.geo_source).to.eq("dadata");
      expect(response.body.restaurant.address.raw_dadata).to.be.an("object");
      expect(response.body.restaurant.address.lat).to.be.a("number");
      expect(response.body.restaurant.address.lng).to.be.a("number");
    });

    cy.contains("Настройки ресторана сохранены", { timeout: 10000 }).should("be.visible");
    cy.contains(".restaurant-dashboard__eta-preview", "30 мин").should("be.visible");
    cy.contains(".address-picker__selected", "Великий Новгород").should("be.visible");
  });
});

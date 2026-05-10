const apiBaseUrl = () => Cypress.env('apiBaseUrl');
const restaurantSlug = () => Cypress.env('restaurantSlug');

const account = (name) => {
  const selected = Cypress.env('accounts')?.[name];

  if (!selected?.email || !selected?.password) {
    throw new Error(`Missing Cypress account credentials for [${name}]. Check cypress.env.json.`);
  }

  return selected;
};

const loginByApi = (credentials) => {
  return cy.request({
    method: 'POST',
    url: `${apiBaseUrl()}/auth/login`,
    body: {
      email: credentials.email,
      password: credentials.password,
      client_type: 'web',
    },
  });
};

const authorize = (credentials) => {
  return loginByApi(credentials).then((response) => response.body.access_token);
};

const visitProtected = (path) => {
  cy.visit(path);
  cy.location('pathname', { timeout: 10000 }).should('eq', path);
};

describe('auth session flow', () => {
  beforeEach(() => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
  });

  it('logs in, restores session with refresh cookie, refreshes access token, and logs out', () => {
    loginByApi(account('user')).then((loginResponse) => {
      expect(loginResponse.status).to.eq(200);
      expect(loginResponse.body.access_token).to.be.a('string').and.not.be.empty;
      expect(loginResponse.body.token_type).to.eq('bearer');
      expect(loginResponse.body.user.email).to.eq(account('user').email);
    });

    cy.request('POST', `${apiBaseUrl()}/auth/refresh`).then((refreshResponse) => {
      expect(refreshResponse.status).to.eq(200);
      expect(refreshResponse.body.access_token).to.be.a('string').and.not.be.empty;
    });

    visitProtected('/profile');
    cy.contains('h1', 'Мой профиль').should('be.visible');
    cy.get('.profile').contains(account('user').email).should('be.visible');

    cy.request('POST', `${apiBaseUrl()}/auth/logout`).its('status').should('eq', 200);

    cy.request({
      method: 'POST',
      url: `${apiBaseUrl()}/auth/refresh`,
      failOnStatusCode: false,
    }).its('status').should('eq', 401);
  });

  it('keeps unauthenticated visitors away from private frontend routes', () => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();

    cy.visit('/profile');
    cy.location('pathname').should('eq', '/');

    cy.visit('/orders');
    cy.location('pathname').should('eq', '/');

    cy.visit('/restaurants/manage');
    cy.location('pathname').should('eq', '/');
  });
});

describe('authenticated route smoke tests', () => {
  beforeEach(() => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
  });

  it('opens core user routes after API login and session restore', () => {
    authorize(account('user'));

    visitProtected('/profile');
    cy.contains('h1', 'Мой профиль').should('be.visible');

    visitProtected('/profile/addresses');
    cy.contains('h1', 'Мои адреса').should('be.visible');

    visitProtected('/orders');
    cy.contains('h1', 'Мои заказы').should('be.visible');

    visitProtected('/cart');
    cy.contains('h1', 'Корзина').should('be.visible');
  });

  it('opens restaurant management routes for a restaurant staff account', () => {
    authorize(account('restaurantOwner'));

    visitProtected('/restaurants/manage');
    cy.contains('h1', 'Мои рестораны').should('be.visible');

    const slug = restaurantSlug();
    visitProtected(`/restaurants/manage/${slug}`);
    cy.contains('Кабинет ресторана').should('be.visible');
  });

  it('opens courier route after creating or refreshing courier profile', () => {
    authorize(account('courier')).then((accessToken) => {
      cy.request({
        method: 'POST',
        url: `${apiBaseUrl()}/courier/profile`,
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
        body: {
          vehicle: 'BIKE',
        },
      }).its('status').should('eq', 200);
    });

    visitProtected('/courier');
    cy.contains('Курьерский кабинет').should('be.visible');
  });
});

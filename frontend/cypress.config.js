import { defineConfig } from 'cypress';

export default defineConfig({
  e2e: {
    baseUrl: 'http://localhost:3000',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'cypress/support/e2e.js',
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
    responseTimeout: 20000,
    video: false,
    env: {
      apiBaseUrl: 'http://localhost:8080/api/v1',
      restaurantSlug: 'picca-na-raione',
      accounts: {
        user: {
          email: 'owner@mail.com',
          password: 'owner123',
        },
        restaurantOwner: {
          email: 'owner@mail.com',
          password: 'owner123',
        },
        courier: {
          email: 'owner@mail.com',
          password: 'owner123',
        },
      },
    },
  },
});

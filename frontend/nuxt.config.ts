// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },
  modules: [
      "@pinia/nuxt",
      'pinia-plugin-persistedstate/nuxt',
  ],
  css: ["~/assets/scss/main.scss"],
  runtimeConfig: {
    public: {
      apiBase:
        process.env.NUXT_PUBLIC_API_BASE || "http://localhost:8080/api/v1",
    },
  },
  typescript: {
    strict: true,
  },
});


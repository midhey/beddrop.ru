// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },
  modules: [
      "@nuxt/eslint",
      "@pinia/nuxt",
  ],
  css: ["~/assets/scss/main.scss"],
  app: {
    head: {
      link: [
        { rel: "icon", type: "image/x-icon", href: "/favicon.ico" },
      ],
    },
  },
  runtimeConfig: {
    public: {
      apiBase:
        process.env.NUXT_PUBLIC_API_BASE || "http://localhost:8080/api/v1",
      mapTileUrl:
        process.env.NUXT_PUBLIC_MAP_TILE_URL || "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
      mapTileAttribution:
        process.env.NUXT_PUBLIC_MAP_TILE_ATTRIBUTION || "© OpenStreetMap contributors",
    },
  },
  typescript: {
    strict: true,
  },
});

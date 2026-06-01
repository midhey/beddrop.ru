import withNuxt from "./.nuxt/eslint.config.mjs";

export default withNuxt({
  files: ["cypress/**/*.js"],
  languageOptions: {
    globals: {
      beforeEach: "readonly",
      Cypress: "readonly",
      cy: "readonly",
      describe: "readonly",
      expect: "readonly",
      it: "readonly",
    },
  },
});

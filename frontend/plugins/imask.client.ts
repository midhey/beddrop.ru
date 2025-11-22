import { defineNuxtPlugin } from '#app';
import { IMaskDirective } from 'vue-imask';

export default defineNuxtPlugin((nuxtApp) => {
    nuxtApp.vueApp.directive('imask', IMaskDirective);
});
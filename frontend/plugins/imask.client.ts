import { defineNuxtPlugin } from '#app';
import type { Directive } from 'vue';
import { IMaskDirective } from 'vue-imask';

export default defineNuxtPlugin((nuxtApp) => {
    nuxtApp.vueApp.directive('imask', IMaskDirective as Directive);
});

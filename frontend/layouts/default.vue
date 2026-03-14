<template>
  <div class="app wrapper">
    <AppHeader/>
    <ActiveOrderBanner />
    <main class="main">
      <slot />
    </main>
    <AppFooter/>
  </div>
</template>

<script setup lang="ts">
import AppHeader from "~/components/layout/AppHeader.vue";
import ActiveOrderBanner from "~/components/orders/ActiveOrderBanner.vue";
import AppFooter from "~/components/layout/AppFooter.vue";
</script>

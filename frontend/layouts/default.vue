<template>
  <div class="app wrapper">
    <AppHeader/>
    <main class="main">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import AppHeader from "~/components/layout/AppHeader.vue";
</script>

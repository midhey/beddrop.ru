<template>
  <div class="app wrapper">
    <AppHeader />
      <main class="main">
        <ClientOnly>
          <NuxtPage/>
        </ClientOnly>
      </main>
  </div>
</template>

<script setup lang="ts">
import AppHeader from "~/components/layout/AppHeader.vue";
</script>
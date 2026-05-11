<script setup lang="ts">
import {
  BarChart3,
  Bike,
  MapPinned,
  ReceiptText,
  Store,
  Users,
} from 'lucide-vue-next';

const navItems = [
  { to: '/admin', label: 'Обзор', icon: BarChart3 },
  { to: '/admin/orders', label: 'Заказы', icon: ReceiptText },
  { to: '/admin/users', label: 'Пользователи', icon: Users },
  { to: '/admin/restaurants', label: 'Рестораны', icon: Store },
  { to: '/admin/couriers', label: 'Курьеры', icon: Bike },
  { to: '/admin/logistics', label: 'Логистика', icon: MapPinned },
];
</script>

<template>
  <section class="admin page-shell">
    <div class="admin__container container">
      <aside class="admin__nav" aria-label="Разделы админки">
        <NuxtLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="admin__nav-item"
          exact-active-class="admin__nav-item--active"
        >
          <component :is="item.icon" class="ui-icon" :size="18" :stroke-width="1.9" aria-hidden="true" />
          <span>{{ item.label }}</span>
        </NuxtLink>
      </aside>

      <main class="admin__main">
        <slot />
      </main>
    </div>
  </section>
</template>

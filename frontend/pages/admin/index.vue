<script setup lang="ts">
import { onMounted } from 'vue';
import AdminShell from '~/components/admin/AdminShell.vue';
import { useAdminDashboard } from '~/composables/useAdmin';
import { formatPrice } from '~/utils/formatting';

useSeoMeta({ title: 'Админка — BedDrop' });

const { dashboard, loading, errorMessage, fetchDashboard } = useAdminDashboard();

const metricCards = computed(() => {
  const metrics = dashboard.value?.metrics ?? {};
  return [
    { label: 'Заказы', value: metrics.orders_total ?? 0 },
    { label: 'Доставлено', value: metrics.orders_delivered ?? 0 },
    { label: 'Отменено', value: metrics.orders_cancelled ?? 0 },
    { label: 'GMV', value: formatPrice(metrics.gmv ?? 0) },
    { label: 'Доставка', value: formatPrice(metrics.delivery_revenue ?? 0) },
    { label: 'Курьерам', value: formatPrice(metrics.courier_payouts ?? 0) },
    { label: 'Сервисный сбор', value: formatPrice(metrics.service_fee_revenue ?? 0) },
    { label: 'Комиссия доставки', value: formatPrice(metrics.service_commission_revenue ?? 0) },
    { label: 'Маржа доставки', value: formatPrice(metrics.delivery_margin ?? 0) },
    { label: 'Доход сервиса', value: formatPrice(metrics.service_revenue_total ?? 0) },
    { label: 'Средний чек', value: formatPrice(metrics.average_check ?? 0) },
    { label: 'Средняя доставка', value: `${Math.round(metrics.average_delivery_minutes ?? 0)} мин` },
  ];
});

onMounted(() => fetchDashboard());
</script>

<template>
  <AdminShell>
    <div class="admin__head">
      <div>
        <h1 class="page-title">Обзор</h1>
        <p class="page-subtitle">Операционные показатели за последние 30 дней.</p>
      </div>
    </div>

    <p v-if="errorMessage" class="state-message state-message--error">{{ errorMessage }}</p>
    <div v-if="loading" class="state-message state-message--loading">Загружаем метрики...</div>

    <template v-else-if="dashboard">
      <div class="admin__metrics">
        <article v-for="card in metricCards" :key="card.label" class="admin__metric">
          <span>{{ card.label }}</span>
          <strong>{{ card.value }}</strong>
        </article>
      </div>

      <div class="admin__grid admin__grid--two">
        <section class="admin__panel">
          <div class="section-head">
            <h2 class="section-title">Динамика по дням</h2>
          </div>
          <table class="admin-table">
            <thead>
              <tr>
                <th>День</th>
                <th>Заказы</th>
                <th>GMV</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in dashboard.daily" :key="row.day">
                <td>{{ row.day }}</td>
                <td>{{ row.orders_count }}</td>
                <td>{{ formatPrice(row.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="admin__panel">
          <div class="section-head">
            <h2 class="section-title">Топ ресторанов</h2>
          </div>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Ресторан</th>
                <th>Заказы</th>
                <th>GMV</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in dashboard.top_restaurants" :key="row.restaurant?.id || row.restaurant_id">
                <td>{{ row.restaurant?.name || 'Ресторан' }}</td>
                <td>{{ row.orders_count }}</td>
                <td>{{ formatPrice(row.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </section>
      </div>
    </template>
  </AdminShell>
</template>

<script setup lang="ts">
import {
  ShoppingBag,
  CheckCircle2,
  XCircle,
  CircleDollarSign,
  Truck,
  Wallet,
  Percent,
  CreditCard,
  TrendingUp,
  BarChart3,
  Timer,
  Store,
} from 'lucide-vue-next';
import AdminShell from '~/components/admin/AdminShell.vue';
import { useAdminDashboard } from '~/composables/useAdmin';
import { formatPrice } from '~/utils/formatting';

useAppSeoMeta({
  title: 'Админка — BedDrop',
  description: 'Административная панель BedDrop с операционными метриками, заказами, ресторанами, курьерами и пользователями.',
  robots: 'noindex,nofollow',
});

const { dashboard, loading, errorMessage, fetchDashboard } = useAdminDashboard();

const metricCards = computed(() => {
  const metrics = dashboard.value?.metrics ?? {};
  return [
    { label: 'Заказы', value: metrics.orders_total ?? 0, icon: ShoppingBag, color: 'indigo' },
    { label: 'Доставленные заказы', value: metrics.orders_delivered ?? 0, icon: CheckCircle2, color: 'success' },
    { label: 'Отменено', value: metrics.orders_cancelled ?? 0, icon: XCircle, color: 'error' },
    { label: 'GMV', value: formatPrice(metrics.gmv ?? 0), icon: CircleDollarSign, color: 'blue' },
    { label: 'Доставка', value: formatPrice(metrics.delivery_revenue ?? 0), icon: Truck, color: 'sky' },
    { label: 'Курьерам', value: formatPrice(metrics.courier_payouts ?? 0), icon: Wallet, color: 'orange' },
    { label: 'Сервисный сбор', value: formatPrice(metrics.service_fee_revenue ?? 0), icon: Percent, color: 'purple' },
    { label: 'Комиссия', value: formatPrice(metrics.service_commission_revenue ?? 0), icon: CreditCard, color: 'indigo' },
    { label: 'Маржа доставки', value: formatPrice(metrics.delivery_margin ?? 0), icon: TrendingUp, color: 'success' },
    { label: 'Доход сервиса', value: formatPrice(metrics.service_revenue_total ?? 0), icon: BarChart3, color: 'blue' },
    { label: 'Средний чек', value: formatPrice(metrics.average_check ?? 0), icon: Wallet, color: 'sky' },
    { label: 'Средняя доставка', value: `${Math.round(metrics.average_delivery_minutes ?? 0)} мин`, icon: Timer, color: 'orange' },
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
        <article
          v-for="card in metricCards"
          :key="card.label"
          class="admin__metric"
          :style="{ '--metric-color': `var(--color-accent-${card.color}, var(--color-${card.color}, var(--color-primary)))` }"
        >
          <div class="admin__metric-header">
            <span>{{ card.label }}</span>
            <div class="admin__metric-icon">
              <component :is="card.icon" class="ui-icon" :size="18" :stroke-width="2" />
            </div>
          </div>
          <strong>{{ card.value }}</strong>
        </article>
      </div>

      <div class="admin__grid admin__grid--two">
        <section class="admin__panel">
          <div class="section-head">
            <div class="section-title-wrap">
              <div class="section-icon">
                <TrendingUp class="ui-icon" :size="18" />
              </div>
              <h2 class="section-title">Динамика по дням</h2>
            </div>
          </div>
          <div class="admin-table-wrapper">
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
                  <td><strong>{{ row.orders_count }}</strong></td>
                  <td>{{ formatPrice(row.revenue) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="admin__panel">
          <div class="section-head">
            <div class="section-title-wrap">
              <div class="section-icon">
                <Store class="ui-icon" :size="18" />
              </div>
              <h2 class="section-title">Топ ресторанов</h2>
            </div>
          </div>
          <div class="admin-table-wrapper">
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
                  <td><strong>{{ row.restaurant?.name || 'Ресторан' }}</strong></td>
                  <td>{{ row.orders_count }}</td>
                  <td>{{ formatPrice(row.revenue) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </template>
  </AdminShell>
</template>

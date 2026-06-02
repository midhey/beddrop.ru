<script setup lang="ts">
import { Info, ArrowLeft, Bike } from 'lucide-vue-next';

useAppSeoMeta({
  title: 'Условия для курьеров — BedDrop',
  description: 'Условия для курьеров BedDrop: правила работы, смены, статусы заказов и демонстрационный формат курьерского кабинета.',
});
</script>

<template>
  <section class="legal-page page-shell">
    <div class="legal-page__container container">
      <button type="button" class="page-back" @click="$router.back()">
        <ArrowLeft :size="16" /> <span>Назад</span>
      </button>

      <div class="legal-page__card surface-card">
        <div class="legal-page__disclaimer">
          <Bike class="legal-page__disclaimer-icon" :size="24" />
          <div class="legal-page__disclaimer-content">
            <span class="legal-page__disclaimer-title">Демонстрация роли</span>
            <p class="legal-page__disclaimer-text">
              Функционал курьера в системе BedDrop предназначен для демонстрации логистических процессов: приема заказов, отслеживания координат и управления статусами доставки в реальном времени.
            </p>
          </div>
        </div>

        <article class="legal-page__content">
          <h1>Условия работы для курьеров</h1>
          
          <p>
            Стать виртуальным курьером BedDrop может любой пользователь, желающий протестировать логистический интерфейс системы.
          </p>

          <h2>Функциональные обязанности (в рамках демо):</h2>
          <ul>
            <li>Прием входящих заказов из общей очереди;</li>
            <li>Имитация перемещения по маршруту (с помощью Geo-симуляции или мобильного приложения);</li>
            <li>Своевременное обновление статусов («В пути», «Доставлено») для информирования клиента.</li>
          </ul>

          <h2>Возможности платформы:</h2>
          <ul>
            <li><strong>Гибкое управление:</strong> возможность выходить на смену и завершать её в любое время;</li>
            <li><strong>Система расчетов:</strong> автоматический расчет вознаграждения за выполненные доставки;</li>
            <li><strong>Навигация:</strong> отображение оптимальных маршрутов до ресторана и клиента.</li>
          </ul>

          <h2>Требования к тестировщику:</h2>
          <p>
            Наличие современного браузера, доступа к геолокации (для корректной работы карты) и понимание того, что все процессы являются имитацией реальной службы доставки.
          </p>
        </article>
      </div>
    </div>
  </section>
</template>

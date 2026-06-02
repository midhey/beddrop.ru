<script setup lang="ts">
import { ArrowLeft, Rocket, Cpu, Palette, Code2 } from 'lucide-vue-next';

useAppSeoMeta({
  title: 'О сервисе — BedDrop',
  description: 'BedDrop — демонстрационная FoodTech-платформа для заказа еды, управления ресторанами, курьерами и логистикой доставки.',
});
</script>

<template>
  <section class="legal-page page-shell about-page">
    <div class="legal-page__container container">
      <button type="button" class="page-back" @click="$router.back()">
        <ArrowLeft :size="16" /> <span>Назад</span>
      </button>

      <div class="legal-page__card surface-card">
        <div class="legal-page__disclaimer">
          <Rocket class="legal-page__disclaimer-icon" :size="24" />
          <div class="legal-page__disclaimer-content">
            <span class="legal-page__disclaimer-title">Проект "BedDrop"</span>
            <p class="legal-page__disclaimer-text">
              BedDrop — это современная технологическая платформа для агрегации ресторанов и управления логистикой доставки, созданная в качестве демонстрационного проекта.
            </p>
          </div>
        </div>

        <article class="legal-page__content">
          <h1>О сервисе</h1>
          
          <p class="lead-text">
            <strong>BedDrop</strong> — это концептуальная платформа для доставки еды, разработанная как демонстрация возможностей современных веб-технологий. Наша цель — показать, как глубокая интеграция геосервисов и продуманный UX могут сделать процесс заказа прозрачным и эффективным.
          </p>

          <div class="features-grid">
            <div class="feature-card">
              <div class="feature-card__icon"><Cpu :size="20" /></div>
              <h3 class="feature-card__title">Умная логистика</h3>
              <p class="feature-card__text">Алгоритмы автоматического распределения заказов и расчёта времени доставки на основе реальных данных.</p>
            </div>
            <div class="feature-card">
              <div class="feature-card__icon"><Palette :size="20" /></div>
              <h3 class="feature-card__title">Современный дизайн</h3>
              <p class="feature-card__text">Адаптивный интерфейс с акцентом на скорость взаимодействия и удобство пользователя на любых устройствах.</p>
            </div>
            <div class="feature-card">
              <div class="feature-card__icon"><Code2 :size="20" /></div>
              <h3 class="feature-card__title">Масштабируемый стек</h3>
              <p class="feature-card__text">Использование Nuxt 4, Laravel 12 и современных паттернов разработки для обеспечения высокой производительности.</p>
            </div>
          </div>

          <h2>Цель проекта</h2>
          <p>
            Проект разработан для демонстрации компетенций в области full-stack разработки. Система включает в себя клиентское приложение, панель управления для ресторанов, специализированный интерфейс для курьеров и административную панель для мониторинга всей экосистемы.
          </p>

          <h2>Технологические решения</h2>
          <p>
            В основе клиентской части лежит <strong>Nuxt 4</strong> — передовой фреймворк на базе Vue 3. Мы используем <strong>TypeScript</strong> для обеспечения типизации и надежности, <strong>Pinia</strong> для управления состоянием и <strong>SCSS</strong> с использованием CSS-переменных для гибкой стилизации. Серверная часть реализована на <strong>Laravel 12</strong>, обеспечивая надежное API и интеграцию с внешними сервисами.
          </p>

          <div class="quote-block">
            "BedDrop — это демонстрация того, как архитектурный подход и внимание к деталям позволяют создавать сложные системы в кратчайшие сроки."
          </div>

          <h2>Миссия</h2>
          <p>
            Мы верим, что качественные технологические продукты должны быть доступны каждому бизнесу, независимо от его размера. BedDrop показывает путь к созданию эффективной и красивой цифровой среды для сферы FoodTech.
          </p>
        </article>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped>
@use "~/assets/scss/core" as *;

.lead-text {
    font-size: rem(18) !important;
    line-height: 1.6;
    color: var(--color-text-strong) !important;
    margin-bottom: rem(32);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(rem(200), 1fr));
    gap: rem(20);
    margin: rem(40) 0;
}

.feature-card {
    padding: rem(24);
    background: var(--color-surface-soft);
    border-radius: var(--radius-16);
    border: 1px solid var(--color-border-neutral);

    &__icon {
        width: rem(40);
        height: rem(40);
        background: var(--color-primary);
        color: white;
        border-radius: var(--radius-12);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: rem(16);
    }

    &__title {
        font-family: var(--font-family-secondary);
        font-size: rem(15);
        font-weight: 800;
        color: var(--color-text-strong);
        margin-bottom: rem(8);
    }

    &__text {
        font-size: rem(13) !important;
        line-height: 1.5;
        color: var(--color-text-subtle) !important;
        margin: 0 !important;
    }
}

.quote-block {
    margin: rem(40) 0;
    padding: rem(24) rem(32);
    border-left: rem(4) solid var(--color-primary);
    background: var(--color-surface-soft);
    font-style: italic;
    font-size: rem(16);
    color: var(--color-text-muted);
    border-radius: 0 var(--radius-16) var(--radius-16) 0;
}
</style>

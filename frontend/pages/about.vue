<script setup lang="ts">
import { Info, ArrowLeft, Rocket, Cpu, Palette, Code2 } from 'lucide-vue-next';
useSeoMeta({ title: 'О сервисе — BedDrop' });
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
              Это не просто сайт с едой, это квинтэссенция бессонных ночей, литров кофе и стремления сделать идеальный UI/UX для дипломной работы. 
            </p>
          </div>
        </div>

        <article class="legal-page__content">
          <h1>О сервисе</h1>
          
          <p class="lead-text">
            <strong>BedDrop</strong> — это концептуальная платформа для доставки еды, разработанная как выпускной проект. Наша цель — показать, как современные технологии могут сделать процесс заказа прозрачным, быстрым и визуально приятным.
          </p>

          <div class="features-grid">
            <div class="feature-card">
              <div class="feature-card__icon"><Cpu :size="20" /></div>
              <h3 class="feature-card__title">Умная логистика</h3>
              <p class="feature-card__text">Алгоритмы симуляции маршрутов и расчёта времени доставки в реальном времени.</p>
            </div>
            <div class="feature-card">
              <div class="feature-card__icon"><Palette :size="20" /></div>
              <h3 class="feature-card__title">Современный дизайн</h3>
              <p class="feature-card__text">Чистый интерфейс с акцентом на контент и удобство пользователя (Mobile First).</p>
            </div>
            <div class="feature-card">
              <div class="feature-card__icon"><Code2 :size="20" /></div>
              <h3 class="feature-card__title">Технологичный стек</h3>
              <p class="feature-card__text">Nuxt 4, TypeScript, Pinia и SCSS — всё по канонам современной веб-разработки.</p>
            </div>
          </div>

          <h2>Зачем это создано?</h2>
          <p>
            Проект создан для демонстрации навыков full-stack разработки. Мы реализовали не только клиентскую часть, которую вы видите, но и сложную систему управления заказами для ресторанов, панель администратора и функционал для курьеров.
          </p>

          <h2>Технологии (для комиссии)</h2>
          <p>
            В основе проекта лежит <strong>Nuxt 4</strong> — передовой фреймворк на базе Vue 3. Мы используем типизацию <strong>TypeScript</strong> для стабильности кода, <strong>Pinia</strong> для управления состоянием и кастомную архитектуру доменов для масштабируемости. Каждая кнопка и каждый переход продуманы так, чтобы пользователь получал удовольствие от использования.
          </p>

          <div class="quote-block">
            "BedDrop — это когда ты хочешь заказать пиццу, но в итоге садишься писать диплом про то, как эту пиццу доставлять."
          </div>

          <h2>Команда</h2>
          <p>
            Над проектом трудился один студент, вдохновленный идеей сделать доставку еды в Иннополисе (и не только) чуточку лучше, хотя бы в рамках браузерной вкладки.
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

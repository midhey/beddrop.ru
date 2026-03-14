<script setup lang="ts">
import { Mail, MessagesSquare, Phone, Send, Youtube } from 'lucide-vue-next';

const year = new Date().getFullYear();

const appName = 'BedDrop';
</script>

<template>
  <footer class="footer">
    <div class="footer__container container">
      <!-- Верхняя часть -->
      <div class="footer__top">
        <!-- Бренд / описание -->
        <div class="footer__brand">
          <NuxtLink to="/" class="footer__logo-link">
            <span class="footer__logo-mark">
              BD
            </span>
            <span class="footer__logo-text">
              {{ appName }}
            </span>
          </NuxtLink>

          <p class="footer__description">
            Сервис доставки из ресторанов: удобный заказ, статус в реальном времени и прозрачная оплата.
          </p>
        </div>

        <!-- Навигация -->
        <nav class="footer__nav">
          <div class="footer__nav-column">
            <h3 class="footer__nav-title">
              Сервис
            </h3>
            <span class="footer__nav-link" aria-disabled="true">
              Рестораны
            </span>
            <NuxtLink to="/orders" class="footer__nav-link">
              Мои заказы
            </NuxtLink>
            <NuxtLink to="/courier" class="footer__nav-link">
              Курьерам
            </NuxtLink>
            <NuxtLink to="/restaurants/manage" class="footer__nav-link">
              Партнёрам-ресторанам
            </NuxtLink>
          </div>

          <div class="footer__nav-column">
            <h3 class="footer__nav-title">
              О компании
            </h3>
            <span class="footer__nav-link" aria-disabled="true">
              О сервисе
            </span>
            <span class="footer__nav-link" aria-disabled="true">
              Контакты
            </span>
            <span class="footer__nav-link" aria-disabled="true">
              Вакансии
            </span>
          </div>

          <div class="footer__nav-column">
            <h3 class="footer__nav-title">
              Документы
            </h3>
            <span class="footer__nav-link" aria-disabled="true">
              Публичная оферта
            </span>
            <span class="footer__nav-link" aria-disabled="true">
              Политика конфиденциальности
            </span>
            <span class="footer__nav-link" aria-disabled="true">
              Условия для курьеров
            </span>
          </div>
        </nav>

        <!-- Контакты / соцсети -->
        <div class="footer__contacts">
          <h3 class="footer__nav-title">
            Поддержка
          </h3>
          <a href="mailto:support@beddrop.local" class="footer__contact-link">
            <Mail class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
            support@beddrop.local
          </a>
          <a href="tel:+79990000000" class="footer__contact-link">
            <Phone class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
            +7 (999) 000-00-00
          </a>

          <div class="footer__socials">
            <button
                type="button"
                class="footer__social-btn"
                aria-label="Мы в Telegram"
            >
              <Send class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
            </button>
            <button
                type="button"
                class="footer__social-btn"
                aria-label="Мы в VK"
            >
              <MessagesSquare class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
            </button>
            <button
                type="button"
                class="footer__social-btn"
                aria-label="Мы в YouTube"
            >
              <Youtube class="ui-icon" :size="16" :stroke-width="1.9" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <!-- Нижняя полоса -->
      <div class="footer__bottom">
        <span class="footer__copy">
          © {{ year }} {{ appName }}. Все права защищены.
        </span>

        <div class="footer__bottom-links">
          <span class="footer__bottom-link" aria-disabled="true">
            Конфиденциальность
          </span>
          <span class="footer__bottom-link" aria-disabled="true">
            Cookies
          </span>
          <button
              type="button"
              class="footer__bottom-link footer__bottom-link--button"
          >
            RU
          </button>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { Info, ArrowLeft } from 'lucide-vue-next';

useAppSeoMeta({
  title: 'Использование Cookies — BedDrop',
  description: 'Политика Cookies BedDrop: зачем сервис использует cookies для авторизации, корзины и стабильной работы интерфейса.',
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
          <Info class="legal-page__disclaimer-icon" :size="24" />
          <div class="legal-page__disclaimer-content">
            <span class="legal-page__disclaimer-title">Вкусные печеньки</span>
            <p class="legal-page__disclaimer-text">
              Cookies в нашем проекте используются для того, чтобы вы не разлогинивались каждую минуту и чтобы корзина помнила ваши виртуальные заказы.
            </p>
          </div>
        </div>

        <article class="legal-page__content">
          <h1>Политика использования Cookies</h1>
          
          <p>
            Наш Сервис использует технологию Cookies для обеспечения стабильной работы интерфейса.
          </p>

          <h2>Что такое Cookies?</h2>
          <p>
            Это маленькие текстовые файлы, которые ваш браузер сохраняет у себя в памяти. Они помогают нам помнить, что именно вы сейчас зашли на сайт, а не кто-то другой.
          </p>

          <h2>Зачем мы их используем?</h2>
          <ul>
            <li><strong>Авторизация:</strong> сохраняем JWT-токен, чтобы вы оставались в системе.</li>
            <li><strong>Корзина:</strong> помним, какие бургеры вы хотели заказать.</li>
            <li><strong>Настройки:</strong> помним ваш выбор города или темы (если бы они были).</li>
          </ul>

          <h2>Как их отключить?</h2>
          <p>
            Вы можете отключить Cookies в настройках браузера, но тогда Сервис перестанет вас узнавать, и вам придется каждый раз заново доказывать, что вы — это вы.
          </p>
        </article>
      </div>
    </div>
  </section>
</template>

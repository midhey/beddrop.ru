<script setup lang="ts">
import { Info, ArrowLeft, Bike } from 'lucide-vue-next';
useSeoMeta({ title: 'Условия для курьеров — BedDrop' });
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
            <span class="legal-page__disclaimer-title">Работа мечты</span>
            <p class="legal-page__disclaimer-text">
              Курьеры в BedDrop — это не реальные люди, а записи в нашей БД. Они никогда не устают, не опаздывают (кроме случаев, когда мы симулируем задержку в коде) и всегда вежливы.
            </p>
          </div>
        </div>

        <article class="legal-page__content">
          <h1>Условия работы для курьеров</h1>
          
          <p>
            Стать курьером BedDrop может любой пользователь, зарегистрировавшийся в системе и нажавший кнопку «Стать курьером» в учебных целях.
          </p>

          <h2>Ваши обязанности (в рамках диплома):</h2>
          <ul>
            <li>Нажимать кнопку «Принять заказ», когда он появляется в очереди;</li>
            <li>Виртуально перемещаться по городу;</li>
            <li>Отмечать доставку заказа кнопкой «Доставлено», чтобы клиент увидел радостное уведомление.</li>
          </ul>

          <h2>Что мы предлагаем?</h2>
          <ul>
            <li><strong>Гибкий график:</strong> работайте, когда открыт ноутбук;</li>
            <li><strong>Высокая зарплата:</strong> выплачивается в виртуальных баллах счастья;</li>
            <li><strong>Карьерный рост:</strong> от «Новичка» до «Магистра кода».</li>
          </ul>

          <h2>Требования:</h2>
          <p>
            Наличие браузера, стабильного интернета и понимание того, что это всё — большая красивая игра в логистику.
          </p>
        </article>
      </div>
    </div>
  </section>
</template>

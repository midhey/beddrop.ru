<script setup lang="ts">
import { Info, ArrowLeft } from 'lucide-vue-next';
useSeoMeta({ title: 'Политика конфиденциальности — BedDrop' });
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
            <span class="legal-page__disclaimer-title">Для приемной комиссии</span>
            <p class="legal-page__disclaimer-text">
              Этот документ объясняет, как мы обрабатываем ваши данные в рамках тестирования учебного проекта. Мы уважаем приватность и не продаем ваши данные (да их и никто не купит, это же диплом).
            </p>
          </div>
        </div>

        <article class="legal-page__content">
          <h1>Политика конфиденциальности</h1>
          <div class="meta-info">
            <span>Редакция от 14 мая 2026 г.</span>
          </div>

          <p>
            Мы, команда разработчиков <strong>BedDrop</strong>, серьезно относимся к безопасности ваших данных, даже если эти данные — это ваш любимый тестовый email <code>test@test.ru</code>.
          </p>

          <h2>1. Какие данные мы собираем</h2>
          <ul>
            <li>Имя (чтобы знать, как к вам обращаться в интерфейсе);</li>
            <li>Номер телефона (для эмуляции подтверждения заказа);</li>
            <li>Адрес (чтобы RouteMap красиво отрисовал путь курьера);</li>
            <li>Email (для входа в систему).</li>
          </ul>

          <h2>2. Зачем нам это нужно</h2>
          <p>
            Исключительно для того, чтобы база данных не пустовала, а интерфейс выглядел живым. Мы используем ваши координаты, чтобы рассчитать расстояние от ресторана и показать вам, через сколько минут (в теории) приедет курьер.
          </p>

          <h2>3. Передача данных третьим лицам</h2>
          <p>
            Ваши данные хранятся на сервере, арендованном студентом на последние деньги. Мы не передаем их рекламным сетям или спецслужбам, если только они не захотят поставить нам «отлично» за диплом.
          </p>

          <h2>4. Права пользователя</h2>
          <p>
            Вы имеете право в любой момент удалить свой профиль или изменить имя на «Анонимный Бургер».
          </p>
        </article>
      </div>
    </div>
  </section>
</template>

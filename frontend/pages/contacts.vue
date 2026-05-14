<script setup lang="ts">
import { Mail, Phone, MapPin, ArrowLeft } from 'lucide-vue-next';
useSeoMeta({ title: 'Контакты — BedDrop' });
</script>

<template>
  <section class="legal-page page-shell">
    <div class="legal-page__container container">
      <button type="button" class="page-back" @click="$router.back()">
        <ArrowLeft :size="16" /> <span>Назад</span>
      </button>

      <div class="legal-page__card surface-card">
        <article class="legal-page__content">
          <h1>Контакты</h1>
          
          <p>
            Если у вас возникли вопросы по работе Сервиса (или вы хотите предложить работу автору проекта), вы можете связаться со мной любым удобным способом.
          </p>

          <div class="contacts-grid">
            <div class="contact-card">
              <Mail class="contact-card__icon" :size="24" />
              <div class="contact-card__info">
                <span class="contact-card__label">Электронная почта</span>
                <a href="mailto:support@beddrop.local" class="contact-card__value">support@beddrop.local</a>
              </div>
            </div>

            <div class="contact-card">
              <Phone class="contact-card__icon" :size="24" />
              <div class="contact-card__info">
                <span class="contact-card__label">Телефон</span>
                <a href="tel:+79990000000" class="contact-card__value">+7 (999) 000-00-00</a>
              </div>
            </div>

            <div class="contact-card">
              <MapPin class="contact-card__icon" :size="24" />
              <div class="contact-card__info">
                <span class="contact-card__label">Локация</span>
                <span class="contact-card__value">г. Иннополис, Университетская 1</span>
              </div>
            </div>
          </div>

          <h2 style="margin-top: 40px;">Обратная связь</h2>
          <p>
            Мы всегда рады конструктивной критике, предложениям по улучшению UI/UX или просто добрым словам перед защитой диплома.
          </p>
        </article>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped>
@use "~/assets/scss/core" as *;

.contacts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(rem(240), 1fr));
  gap: rem(20);
  margin-top: rem(32);
}

.contact-card {
  padding: rem(24);
  background: var(--color-surface-soft);
  border: 1px solid var(--color-border-neutral);
  border-radius: var(--radius-16);
  display: flex;
  gap: rem(16);
  align-items: center;
  transition: all 0.2s ease;

  &:hover {
    border-color: var(--color-primary);
    transform: translateY(rem(-4));
    background: var(--color-surface);
    box-shadow: 0 4px 12px rgba(122, 59, 255, 0.08);
  }

  &__icon {
    color: var(--color-primary);
    flex-shrink: 0;
  }

  &__info {
    display: flex;
    flex-direction: column;
    gap: rem(2);
  }

  &__label {
    font-size: rem(12);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text-soft);
  }

  &__value {
    font-size: rem(16);
    font-weight: 700;
    color: var(--color-text-strong);
    text-decoration: none;
    
    &[href]:hover {
        color: var(--color-primary);
    }
  }
}
</style>

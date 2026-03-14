<script setup lang="ts">
import { computed } from "vue";
import type { NuxtError } from "#app";
import { SearchX } from "lucide-vue-next";

const props = defineProps<{
  error: NuxtError;
}>();

const statusCode = computed(() => {
  const code = Number(props.error?.statusCode);
  return Number.isFinite(code) && code > 0 ? code : 500;
});

const isNotFound = computed(() => statusCode.value === 404);

const title = computed(() =>
  isNotFound.value ? "Страница не найдена" : "Не удалось открыть страницу",
);

const description = computed(() =>
  isNotFound.value
    ? "Проверьте адрес или вернитесь к списку ресторанов."
    : "Попробуйте повторить действие позже или вернитесь на главную.",
);

const details = computed(() => {
  const statusMessage = props.error?.statusMessage?.trim();
  const normalizedStatusMessage = statusMessage?.toLowerCase();

  if (
    statusMessage &&
    normalizedStatusMessage !== "page not found" &&
    normalizedStatusMessage !== "not found"
  ) {
    return statusMessage;
  }

  return isNotFound.value
    ? "Запрошенный адрес не существует, был перемещен или еще не опубликован."
    : "Если ошибка повторяется, проверьте подключение или попробуйте открыть страницу позже.";
});

const errorBadge = computed(() =>
  isNotFound.value ? "Ошибка 404" : `Ошибка ${statusCode.value}`,
);

useHead(() => ({
  title: isNotFound.value ? "404 | Beddrop" : `${statusCode.value} | Beddrop`,
  meta: [{ name: "robots", content: "noindex" }],
}));

const handleHome = async () => {
  await clearError({ redirect: "/" });
};

const handleBack = async () => {
  if (import.meta.client && window.history.length > 1) {
    await clearError();
    window.history.back();
    return;
  }

  await handleHome();
};
</script>

<template>
  <NuxtLayout name="default">
    <section class="error-page">
      <div class="error-page__container">
        <div class="error-page__card">
          <div class="error-page__glow" />

          <div class="error-page__media" aria-hidden="true">
            <div class="error-page__icon-wrap">
              <SearchX
                class="error-page__icon ui-icon"
                :size="44"
                :stroke-width="1.9"
              />
            </div>

            <div class="error-page__code">
              {{ statusCode }}
            </div>
          </div>

          <div class="error-page__content">
            <span class="error-page__badge">{{ errorBadge }}</span>

            <h1 class="error-page__title">
              {{ title }}
            </h1>

            <p class="error-page__description">
              {{ description }}
            </p>

            <p class="error-page__details">
              {{ details }}
            </p>

            <div class="error-page__actions">
              <button
                type="button"
                class="button button--icon"
                @click="handleHome"
              >
                <span>На главную</span>
              </button>

              <button
                type="button"
                class="button button--ghost button--icon"
                @click="handleBack"
              >
                <span>Назад</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </NuxtLayout>
</template>

# Frontend

Frontend-клиент проекта Beddrop на `Nuxt 4` и `Vue 3`. Приложение работает как витрина и пользовательский кабинет для клиентов, ресторанов и курьеров, используя backend API из `backend/`.

## Обзор

Технологический стек:

- Nuxt `^4.2.1`
- Vue `^3.5`
- TypeScript в `strict`-режиме
- Pinia для клиентского состояния
- in-memory auth-state без сохранения access token в `localStorage`
- Axios для работы с backend API
- Sass/SCSS для стилизации
- `lucide-vue-next`, `notiflix`, `imask`, `swiper`

Frontend подключается к backend через `runtimeConfig.public.apiBase`. По умолчанию используется:

```text
http://localhost:8080/api/v1
```

## Что умеет приложение

На текущий момент frontend покрывает несколько пользовательских зон:

- публичная главная страница со списком ресторанов
- страница ресторана с меню и фильтрацией по категориям
- корзина и оформление заказа
- профиль пользователя и адреса
- список и детали заказов
- кабинет ресторана и управление своими точками
- страница инвайта сотрудника ресторана
- кабинет курьера со сменами и заказами
- авторизация и регистрация

## Структура

```text
frontend/
├── app.vue
├── assets/
├── components/
├── composables/
├── domains/
├── layouts/
├── middleware/
├── pages/
├── plugins/
├── public/
├── stores/
├── utils/
├── nuxt.config.ts
└── package.json
```

## Архитектура

### `pages/`

Маршруты Nuxt, соответствующие пользовательским экранам:

- `/` - список ресторанов
- `/restaurants/[slug]` - карточка ресторана и меню
- `/cart` - корзина
- `/orders` и `/orders/[id]` - история и детали заказов
- `/orders/create` - оформление заказа
- `/profile` - профиль пользователя
- `/profile/addresses` - адреса доставки
- `/restaurants/manage` - список ресторанов текущего пользователя
- `/restaurants/manage/[slug]` - кабинет конкретного ресторана
- `/restaurants/staff-invites/[token]` - принятие инвайта сотрудника
- `/courier` - кабинет курьера

### `components/`

Переиспользуемые UI-блоки и доменные компоненты:

- `layout` - шапка, футер, каркас приложения
- `auth` - формы логина и регистрации
- `restaurants` - карточки ресторанов
- `product` - карточки товаров
- `cart` - элементы корзины
- `orders` - активный заказ и связанные блоки
- `address` - поля адреса
- `ui` - модалки, dropdown, accordion и общие контейнеры

### `composables/`

Локальная прикладная логика страниц и сценариев:

- загрузка данных страниц
- форматирование и derived state
- orchestration действий над корзиной, заказами, ресторанами и курьером
- работа с feedback и upload-сценариями

### `domains/`

Тонкий доменный слой поверх API и представления данных.

Примеры:

- `domains/restaurants/api.ts`
- `domains/orders/api.ts`
- `domains/courier/api.ts`

Этот слой отделяет сетевые запросы и маппинг данных от компонентов страниц.

### `stores/`

Глобальное клиентское состояние на Pinia:

- `auth` - in-memory access token, пользователь, silent session restore, login/register/logout/refresh/profile
- `cart` - текущая корзина и действия над товарами
- `app-shell` - bootstrap приложения после авторизации, права доступа, активный заказ

### `plugins/`

Ключевые клиентские плагины:

- `api.ts` - Axios instance с `baseURL`, `Authorization` header, `withCredentials` и авто-refresh access token
- `auth-init.client.ts` - silent session restore через `/auth/refresh` и bootstrap приложения
- `notiflix.client.ts` - пользовательские уведомления
- `imask.client.ts` - маски для ввода

### `middleware/`

Глобальный route middleware `access.global.ts` контролирует доступ к приватным зонам:

- `/orders`
- `/courier`
- `/restaurants/manage`

Если пользователь не авторизован или не имеет нужного доступа, происходит редирект на главную страницу.

## Интеграция с backend

Frontend ожидает, что backend доступен и предоставляет API из [backend/README.md](/Users/midhey/devhub/web/beddrop.ru/backend/README.md).

Базовые сценарии интеграции:

- авторизация через короткоживущий access token и refresh cookie
- загрузка ресторанов и меню
- управление корзиной
- создание заказов
- работа с адресами пользователя
- доступ к ресторанному кабинету через `/restaurants/my`
- доступ к кабинету курьера через `/courier/profile`

Access token живёт только в памяти клиента. После reload frontend делает silent `/auth/refresh` по `HttpOnly` refresh cookie, а при истечении access token автоматически перевыпускает его через единый refresh flow.

## Конфигурация

Ключевая runtime-настройка находится в `nuxt.config.ts`:

```ts
runtimeConfig: {
  public: {
    apiBase: process.env.NUXT_PUBLIC_API_BASE || "http://localhost:8080/api/v1",
  },
}
```

Для локальной разработки можно переопределить адрес backend через переменную окружения:

```bash
NUXT_PUBLIC_API_BASE=http://localhost:8080/api/v1
```

## Локальный запуск

Установка зависимостей:

```bash
npm install
```

Запуск dev-сервера:

```bash
npm run dev
```

Сборка production-версии:

```bash
npm run build
```

Предпросмотр production-сборки:

```bash
npm run preview
```

Генерация статической версии:

```bash
npm run generate
```

По умолчанию Nuxt dev-сервер будет доступен на стандартном локальном порту Nuxt, если он не переопределен окружением.

## Стилизация

Стили подключаются глобально через:

- `assets/scss/main.scss`

SCSS-слои разделены на:

- `core` - переменные, функции, mixins, placeholders
- `base` - reset, global, fonts, utilities
- `modules` - общие интерактивные модули
- `blocks` - стили конкретных экранов и компонентных блоков

В проекте уже используются кастомные шрифты из `assets/fonts`, включая `Unbounded` и `Open Sans`.

## Пользовательские сценарии

### Гость

Может:

- просматривать рестораны
- открывать карточку ресторана
- смотреть меню

Для работы с корзиной и заказами потребуется авторизация.

### Авторизованный пользователь

Может:

- управлять профилем
- хранить адреса доставки
- добавлять товары в корзину
- оформлять заказ
- просматривать историю заказов

### Сотрудник ресторана

При наличии доступа видит кабинет своих ресторанов и может перейти в управление конкретной точкой.

### Курьер

При наличии активного courier profile получает доступ к `/courier`, где может:

- открыть и закрыть смену
- смотреть доступные заказы
- брать заказ в работу
- отмечать этапы доставки
- просматривать историю

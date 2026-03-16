# Beddrop

Beddrop - сервис доставки еды с разделением на клиентскую витрину, backend API и локальную Docker-инфраструктуру для разработки.

Репозиторий состоит из трех основных частей:

- [frontend/README.md](https://github.com/midhey/beddrop.ru/blob/master/frontend/README.md) - клиентское приложение на Nuxt 4 / Vue 3
- [backend/README.md](https://github.com/midhey/beddrop.ru/blob/master/backend/README.md) - REST API на Laravel 12
- [infra/README.md](https://github.com/midhey/beddrop.ru/blob/master/infra/README.md) - локальная инфраструктура на Docker Compose

## Обзор

Проект реализует базовые сценарии food delivery:

- просмотр ресторанов и меню
- регистрация и авторизация пользователей
- работа с корзиной и оформление заказа
- история заказов клиента
- кабинет ресторана
- кабинет курьера
- управление адресами, медиа и ролями сотрудников ресторана

С технической стороны проект разделен на:

- `frontend` - UI, клиентские сценарии и интеграция с API
- `backend` - бизнес-логика, JWT-аутентификация, работа с БД
- `infra` - контейнеры `nginx`, `php-fpm`, `mysql`, `redis`, `queue`

## Структура репозитория

```text
.
├── backend/
├── frontend/
├── infra/
└── README.md
```

## Архитектура

Общий поток запроса выглядит так:

1. Пользователь открывает frontend-приложение Nuxt
2. Frontend обращается к Laravel API по `http://localhost:8080/api/v1`
3. Nginx из `infra/` принимает HTTP-запросы и проксирует PHP-запросы в контейнер `app`
4. Backend работает с MySQL и Redis
5. Отдельный контейнер `queue` готов обрабатывать фоновые задачи

Упрощенная схема:

```text
Browser
  -> Frontend (Nuxt)
  -> Backend API (/api/v1)
  -> Nginx
  -> PHP-FPM (Laravel)
  -> MySQL / Redis
```

## Технологии

### Frontend

- Nuxt 4
- Vue 3
- TypeScript
- Pinia
- Axios
- SCSS

### Backend

- PHP 8.2
- Laravel 12
- JWT (`tymon/jwt-auth`)
- MySQL
- Redis
- PHPUnit

### Infra

- Docker Compose
- Nginx
- MySQL 8
- Redis 7

## Быстрый старт

### 1. Поднять инфраструктуру

Из корня репозитория:

```bash
docker compose -f infra/docker-compose.yml up --build
```

После запуска будут доступны:

- backend через `http://localhost:8080`
- MySQL через порт `3306`
- Redis через порт `6379`

### 2. Настроить backend

Если `backend/.env` еще не подготовлен:

```bash
cp backend/.env.example backend/.env
```

Ключевые значения для Docker-сценария:

```env
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=beddrop
DB_USERNAME=beddrop
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=database
```

Дальше внутри backend нужно сгенерировать ключи и прогнать миграции:

```bash
docker compose -f infra/docker-compose.yml exec app php artisan key:generate
docker compose -f infra/docker-compose.yml exec app php artisan jwt:secret
docker compose -f infra/docker-compose.yml exec app php artisan migrate
```

При необходимости наполнить базу демо-данными:

```bash
docker compose -f infra/docker-compose.yml exec app php artisan db:seed
```

### 3. Запустить frontend

В отдельном терминале:

```bash
cd frontend
npm install
npm run dev
```

Если нужно явно указать backend API:

```bash
NUXT_PUBLIC_API_BASE=http://localhost:8080/api/v1 npm run dev
```

## Компоненты

### `frontend/`

Отвечает за:

- публичную витрину ресторанов
- карточку ресторана и меню
- корзину и оформление заказа
- профиль пользователя и адреса
- историю заказов
- кабинет ресторана
- кабинет курьера

Подробнее: [frontend/README.md](https://github.com/midhey/beddrop.ru/blob/master/frontend/README.md)

### `backend/`

Отвечает за:

- JWT-аутентификацию
- пользователей и адреса
- рестораны, роли сотрудников и инвайты
- категории, товары, изображения и медиа
- корзины и заказы
- курьерские профили и смены

Подробнее: [backend/README.md](https://github.com/midhey/beddrop.ru/blob/master/backend/README.md)

### `infra/`

Отвечает за локальное окружение разработки:

- `app` - Laravel в PHP-FPM
- `nginx` - HTTP entrypoint
- `mysql` - база данных
- `redis` - кэш и вспомогательная инфраструктура
- `queue` - воркер очередей

Подробнее: [infra/README.md](https://github.com/midhey/beddrop.ru/blob/master/infra/README.md)

## Demo-данные

После `php artisan db:seed` доступны тестовые пользователи:

- `admin@mail.com` / `admin123`
- `owner@mail.com` / `owner123`
- `manager@mail.com` / `manager123`
- `staff@mail.com` / `staff123`

Сиды также создают демо-рестораны, категории и товары.

## Полезные команды

Поднять инфраструктуру в фоне:

```bash
docker compose -f infra/docker-compose.yml up -d --build
```

Остановить инфраструктуру:

```bash
docker compose -f infra/docker-compose.yml down
```

Посмотреть backend-маршруты:

```bash
docker compose -f infra/docker-compose.yml exec app php artisan route:list
```

Запустить backend-тесты:

```bash
docker compose -f infra/docker-compose.yml exec app php artisan test
```

Запустить frontend в dev-режиме:

```bash
cd frontend && npm run dev
```

## Текущее состояние проекта

На данный момент:

- backend уже покрывает основные домены food delivery
- frontend реализует клиентскую, ресторанную и курьерскую зоны
- локальная инфраструктура рассчитана в первую очередь на backend
- отдельный контейнер `queue` присутствует, но в текущем коде backend очереди пока фактически не используются бизнес-логикой

## Документация по папкам

- [frontend/README.md](https://github.com/midhey/beddrop.ru/blob/master/frontend/README.md)
- [backend/README.md](https://github.com/midhey/beddrop.ru/blob/master/backend/README.md)
- [infra/README.md](https://github.com/midhey/beddrop.ru/blob/master/infra/README.md)

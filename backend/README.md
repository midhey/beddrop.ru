# Backend

Backend-сервис проекта Beddrop на Laravel 12. Приложение предоставляет REST API для аутентификации, профиля пользователя, ресторанов, каталога, корзины, заказов, курьеров и медиа.

## Обзор

Технологический стек:

- PHP `^8.2`
- Laravel `^12.0`
- JWT-аутентификация через `tymon/jwt-auth`
- MySQL как основная база данных
- Redis для кэша и очередей
- PHPUnit для тестов

Backend ориентирован на API-first сценарий. Основные маршруты находятся в `routes/api.php`, а бизнес-логика вынесена в `app/Actions`, `app/Http/Controllers`, `app/Policies` и `app/Models`.

## Основные домены

В текущей версии backend покрывает следующие сущности и сценарии:

- `Auth` - регистрация, логин, refresh/logout по JWT
- `Profile` - профиль пользователя, смена пароля, адреса
- `Restaurants` - список ресторанов, карточка ресторана, управление своими ресторанами
- `Restaurant Staff` - сотрудники ресторана, роли, инвайты
- `Products` - категории, товары, изображения товаров
- `Cart` - активная корзина пользователя
- `Orders` - создание и просмотр заказов
- `Courier` - профиль курьера, смены, доступные/активные/завершенные доставки
- `Media` - загрузка и удаление медиафайлов

## Структура

```text
backend/
├── app/
│   ├── Actions/
│   ├── Enums/
│   ├── Http/
│   ├── Models/
│   ├── Observers/
│   ├── Policies/
│   └── Providers/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
├── routes/
├── storage/
└── tests/
```

## Архитектура

### `app/Http/Controllers`

Точка входа для API. Контроллеры сгруппированы по доменам:

- `Auth`
- `Profile`
- `Restaurant`
- `Product`
- `Courier`

### `app/Actions`

Прикладные действия, которые инкапсулируют бизнес-операции. Например:

- работа с корзиной
- создание заказа
- переходы заказа у ресторана
- переходы заказа у курьера
- управление сотрудниками ресторана

### `app/Models`

Ключевые модели домена:

- `User`
- `Address`
- `Restaurant`
- `ProductCategory`
- `Product`
- `ProductImage`
- `Cart`, `CartItem`
- `Order`, `OrderItem`, `OrderEvent`
- `CourierProfile`, `CourierShift`
- `Media`
- `RestaurantStaffInvite`

### `app/Enums`

Enum-классы описывают статусные модели и ролевую логику:

- статусы заказа и оплаты
- роли сотрудников ресторана
- статусы и транспорт курьера
- статусы корзины

### `tests/Feature`

Feature-тесты покрывают ключевые сценарии:

- права доступа сотрудников ресторана
- жизненный цикл заказа ресторана
- жизненный цикл заказа курьера
- инвайты сотрудников
- корзину и ограничения по ресторану
- видимость ресторанов, товаров и медиа

## API

Базовый префикс API:

```text
/api/v1
```

Ключевые группы маршрутов:

- `/auth` - регистрация, логин, refresh, logout
- `/profile` - текущий пользователь и пароль
- `/restaurants` - публичные и приватные операции с ресторанами
- `/staff-invites` - просмотр и принятие инвайта сотрудника
- `/product-categories` - категории товаров
- `/cart` - корзина пользователя
- `/orders` - заказы клиента
- `/courier` - профиль курьера, смены, заказы доставки
- `/addresses` - адреса пользователя
- `/media` - загрузка и удаление файлов

Проверка доступности API:

```http
GET /api/ping
```

Ответ:

```json
{
  "message": "pong"
}
```

## Аутентификация

API использует guard `api` с JWT-драйвером:

- защищенные маршруты работают через middleware `auth:api`
- пользовательская модель `User` реализует `JWTSubject`
- для работы токенов нужен `JWT_SECRET`

После первичной настройки секрета:

```bash
php artisan jwt:secret
```

Ожидаемый сценарий:

1. Пользователь регистрируется или логинится
2. Клиент получает JWT access token
3. Токен передается в `Authorization: Bearer <token>`
4. При необходимости токен обновляется через `/api/v1/auth/refresh`

## Локальный запуск

### Вариант 1. Через Docker Compose

Инфраструктура проекта описана в [infra/README.md](https://github.com/midhey/beddrop.ru/blob/master/infra/README.md).

Основной сценарий:

```bash
docker compose -f infra/docker-compose.yml up --build
```

### Вариант 2. Локально без Docker

Установка зависимостей:

```bash
composer install
```

Создание env-файла:

```bash
cp .env.example .env
```

Генерация ключей:

```bash
php artisan key:generate
php artisan jwt:secret
```

Запуск миграций:

```bash
php artisan migrate
```

Запуск приложения:

```bash
php artisan serve
```

Запуск воркера очередей:

```bash
php artisan queue:work
```

## Переменные окружения

Минимально важные настройки для запуска:

```env
APP_NAME=Beddrop
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=beddrop
DB_USERNAME=beddrop
DB_PASSWORD=secret

QUEUE_CONNECTION=database

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379

JWT_SECRET=
```

Если backend запускается вне Docker, значения `DB_HOST` и `REDIS_HOST` должны соответствовать локальному окружению.

## База данных

Миграции создают структуру для:

- пользователей и адресов
- ресторанов и сотрудников ресторана
- категорий, товаров и изображений
- корзин и заказов
- событий заказов
- профилей и смен курьеров
- очередей, кэша и сессий Laravel

Отдельно есть миграции для:

- инвайтов сотрудников ресторана
- описания ресторана
- комиссии курьера в заказе

## Сидирование

В `DatabaseSeeder` подключены:

- `UserSeeder`
- `MediaSeeder`
- `RestaurantSeeder`
- `ProductCategorySeeder`
- `ProductSeeder`

Запуск:

```bash
php artisan db:seed
```

Demo-пользователи из `UserSeeder`:

- `admin@mail.com` / `admin123`
- `owner@mail.com` / `owner123`
- `manager@mail.com` / `manager123`
- `staff@mail.com` / `staff123`

Сиды также создают демо-рестораны, категории и товары.

## Тестирование

Запуск всех тестов:

```bash
php artisan test
```

Или через composer-скрипт:

```bash
composer test
```

Тестовое окружение использует `sqlite` in-memory, что задано в `phpunit.xml`.

## Полезные команды

Очистить кэш конфигурации:

```bash
php artisan config:clear
```

Посмотреть список маршрутов:

```bash
php artisan route:list
```

Пересоздать БД и сиды:

```bash
php artisan migrate:fresh --seed
```

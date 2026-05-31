# Infra

Папка `infra/` содержит локальную инфраструктуру проекта на базе Docker для запуска backend-приложения.

## Обзор

В каталоге описано окружение для Laravel backend со следующими сервисами:

- `app` - PHP-FPM контейнер, собираемый из `backend/Dockerfile`
- `nginx` - HTTP-вход и прокси до PHP-FPM
- `mysql` - база данных MySQL 8.0
- `redis` - Redis 7 для кэша
- `queue` - отдельный воркер Laravel очередей

Все сервисы подключены к общей сети `beddrop-network`.

## Структура

```text
infra/
├── .env.example
├── docker-compose.yml
└── nginx/
    ├── backend.conf
    └── nginx.conf
```

## Состав

### `docker-compose.yml`

Описывает локальную схему сервисов:

- собирает `app` и `queue` из исходников backend
- монтирует `../backend` внутрь контейнеров для работы с кодом без пересборки
- публикует HTTP на `8080`, а MySQL и Redis только на `127.0.0.1:3306` и `127.0.0.1:6379`
- сохраняет данные MySQL и Redis в именованных Docker volumes

### `nginx/nginx.conf`

Основной конфиг Nginx, который используется контейнером `nginx`.

Что делает:

- раздает Laravel из `/var/www/html/public`
- перенаправляет все application-запросы через `index.php`
- отправляет PHP-обработку в `app:9000`
- запрещает доступ к скрытым `.ht*` файлам

### `nginx/backend.conf`

Альтернативный `server`-конфиг для backend.

Сейчас он не подключен в `docker-compose.yml`, поэтому активным конфигом остается `nginx.conf`.

### `.env.example`

Шаблон переменных окружения для инфраструктурных сервисов. Перед запуском его нужно скопировать в `infra/.env`.
В текущем Compose-файле эти переменные используются контейнером MySQL:

- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_ROOT_PASSWORD`

Значения в `.env.example` предназначены только для локальной разработки и не являются production-секретами.

## Как это работает

Сценарий обработки запроса:

1. Клиент отправляет HTTP-запрос на `http://localhost:8080`
2. `nginx` отдает статику или проксирует запрос в `app:9000`
3. `app` исполняет Laravel через PHP-FPM
4. Laravel использует `mysql` и `redis` по внутренней Docker-сети
5. Фоновые задачи обрабатываются отдельным сервисом `queue`

## Локальный запуск

Из корня репозитория:

```bash
cp infra/.env.example infra/.env
cp backend/.env.example backend/.env
```

Первый запуск в фоне:

```bash
docker compose -f infra/docker-compose.yml up -d --build
```

Сгенерируйте Laravel-секреты после первого старта контейнеров:

```bash
docker compose -f infra/docker-compose.yml exec app php artisan key:generate
docker compose -f infra/docker-compose.yml exec app php artisan jwt:secret
```

Запуск в foreground-режиме для просмотра логов:

```bash
docker compose -f infra/docker-compose.yml up --build
```

Остановка сервисов:

```bash
docker compose -f infra/docker-compose.yml down
```

Остановка с удалением volumes удалит локальные данные MySQL/Redis:

```bash
docker compose -f infra/docker-compose.yml down -v
```

## Настройки backend

Инфраструктура поднимает MySQL и Redis, но Laravel должен быть настроен на эти контейнеры в `backend/.env`.

Типичные значения для локальной Docker-разработки:

```env
APP_URL=http://localhost:8080
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=beddrop
DB_USERNAME=beddrop
DB_PASSWORD=local_password

REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=database
```

Текущая проектная политика для очередей - `QUEUE_CONNECTION=database`; сервис `queue` запускает `php artisan queue:work`.

Если backend запускается на хосте без Docker, замените `DB_HOST=mysql` на `127.0.0.1` и `REDIS_HOST=redis` на `127.0.0.1`.

## Секреты, ngrok и YooKassa

Не коммитьте реальные значения из `infra/.env` и `backend/.env`. В репозитории должны храниться только `*.example` с безопасными placeholders.

Для локального ngrok меняйте только `backend/.env`, например:

```env
APP_URL=https://your-ngrok-host.ngrok-free.app
FRONTEND_URL=http://localhost:3000
YOOKASSA_RETURN_URL=http://localhost:3000/orders
YOOKASSA_WEBHOOK_URL=https://your-ngrok-host.ngrok-free.app/api/v1/payments/yookassa/webhook
```

Для YooKassa задавайте реальные `YOOKASSA_SHOP_ID` и `YOOKASSA_SECRET_KEY` только в `backend/.env` или в секретах окружения деплоя. После изменения env внутри контейнера очистите конфиг:

```bash
docker compose -f infra/docker-compose.yml exec app php artisan config:clear
```

## Полезные команды

Войти в контейнер `app`:

```bash
docker compose -f infra/docker-compose.yml exec app sh
```

Запустить миграции:

```bash
docker compose -f infra/docker-compose.yml exec app php artisan migrate
```

Посмотреть логи:

```bash
docker compose -f infra/docker-compose.yml logs -f
```

Посмотреть логи только воркера очередей:

```bash
docker compose -f infra/docker-compose.yml logs -f queue
```

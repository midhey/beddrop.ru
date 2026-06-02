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

## Production deploy

Production-деплой описан в корневых файлах:

- `docker-compose.prod.yml` - production Compose-схема для сервера
- `backend/Dockerfile.prod` - backend image с PHP-FPM и Laravel
- `frontend/Dockerfile.prod` - frontend image с Nuxt
- `.github/workflows/deploy.yml` - ручной GitHub Actions workflow
- `infra/nginx/production.conf` - Nginx-конфиг backend-прокси до PHP-FPM

Web/API версия управляется Release Please компонентом `beddrop-web`. Deploy можно запускать вручную, но основной production-flow такой:

1. Коммиты с `fix(web): ...` или `feat(web): ...` попадают в `master`.
2. Workflow `Release Please` создает release PR для `beddrop-web`.
3. После merge release PR создается GitHub Release и tag `beddrop-web-v<version>`.
4. Workflow `Deploy` автоматически собирает backend/frontend images с tag `<version>` и деплоит их на сервер.

### Production-схема

На сервере глобальный Caddy остается отдельным compose-проектом и проксирует домены:

```caddyfile
beddrop.ru {
    encode gzip
    reverse_proxy beddrop-frontend:3000
}

api.beddrop.ru {
    encode gzip
    reverse_proxy beddrop-nginx:80
}
```

Beddrop поднимается отдельным compose-проектом в `DEPLOY_PATH`, сейчас это `/root/beddrop`.

Production services:

- `beddrop-frontend` - Nuxt server на `3000`
- `beddrop-nginx` - Nginx backend gateway на `80`
- `beddrop-app` - Laravel PHP-FPM
- `beddrop-queue` - Laravel queue worker
- `beddrop-scheduler` - Laravel scheduler
- `beddrop-mysql` - internal MySQL 8.0
- `beddrop-redis` - internal Redis 7

Compose подключается к external Docker network `valhalla_web`, чтобы Caddy видел `beddrop-frontend` и `beddrop-nginx`, а backend мог обращаться к Valhalla по `http://valhalla:8002`.

### GitHub Secrets

Secrets хранятся в GitHub:

`Settings -> Secrets and variables -> Actions -> Repository secrets`

Required deploy secrets:

```text
SSH_HOST=89.223.64.100
SSH_PORT=22
SSH_USER=root
SSH_PRIVATE_KEY=<private deploy key>
DEPLOY_PATH=/root/beddrop
GHCR_USERNAME=midhey
GHCR_TOKEN=<token with packages read access, если GHCR package private>
```

Required backend secrets:

```text
APP_KEY
JWT_SECRET
MYSQL_DATABASE
MYSQL_USER
MYSQL_PASSWORD
MYSQL_ROOT_PASSWORD
DADATA_API_KEY
DADATA_SECRET_KEY
YOOKASSA_SHOP_ID
YOOKASSA_SECRET_KEY
```

`SSH_PRIVATE_KEY` должен быть private key целиком, включая строки:

```text
-----BEGIN OPENSSH PRIVATE KEY-----
...
-----END OPENSSH PRIVATE KEY-----
```

Не вставляйте `.pub`-ключ в `SSH_PRIVATE_KEY`.

### Ручной запуск deploy

Через GitHub UI:

1. Откройте `Actions`
2. Выберите workflow `Deploy`
3. Нажмите `Run workflow`

Через GitHub CLI:

```bash
gh workflow run deploy.yml --repo midhey/beddrop.ru
```

Можно указать конкретный image tag через input `image_tag`; если не указать, будет использован commit SHA текущего запуска.

Автоматический deploy запускается только для GitHub Releases с tag `beddrop-web-v*`.

### Управление web-версией

Release Please смотрит conventional commits:

```text
fix(web): поправить CORS              -> patch release
feat(web): добавить оплату            -> minor release
feat(web)!: изменить API контракт     -> major release
```

Backend и frontend версионируются вместе как один компонент `beddrop-web`, потому что деплоятся одним production compose.

Посмотреть последние запуски:

```bash
gh run list --repo midhey/beddrop.ru --workflow deploy.yml --limit 5
```

Перезапустить только упавшие jobs:

```bash
gh run rerun RUN_ID --repo midhey/beddrop.ru --failed
```

### Что делает workflow

1. Запускает backend tests.
2. Запускает frontend build.
3. Собирает и пушит Docker images в GHCR:
   - `ghcr.io/midhey/beddrop-backend:<version-or-github-sha>`
   - `ghcr.io/midhey/beddrop-frontend:<version-or-github-sha>`
   - также обновляет tag `latest`
4. По SSH создает или обновляет `DEPLOY_PATH`.
5. Копирует на сервер:
   - `docker-compose.prod.yml`
   - `infra/nginx/production.conf`
   - `compose.env`
   - `.env`
6. Делает `docker compose pull`.
7. Запускает `docker compose up -d --remove-orphans`.
8. Перезапускает `beddrop-nginx`, чтобы Nginx заново зарезолвил IP `beddrop-app`.
9. Выполняет:
   - `php artisan migrate --force`
   - `php artisan optimize:clear`
   - `php artisan config:cache`
   - `php artisan route:cache`
10. Проверяет:
    - `https://api.beddrop.ru/up`
    - `https://api.beddrop.ru/api/ping`

### Caddy после изменения доменов

Если менялся `/root/valhalla/Caddyfile`, обычного reload может быть недостаточно, если файл был заменен и контейнер держит старый bind-mounted inode.

Сначала можно попробовать reload:

```bash
cd /root/valhalla
docker compose exec caddy caddy reload --config /etc/caddy/Caddyfile
```

Если Caddy внутри контейнера не видит новый файл, пересоздайте только Caddy:

```bash
cd /root/valhalla
docker compose up -d --force-recreate caddy
```

Проверить Caddyfile внутри контейнера:

```bash
docker exec caddy sed -n '1,220p' /etc/caddy/Caddyfile
```

### Проверки на сервере

Контейнеры:

```bash
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Networks}}'
```

Логи backend gateway:

```bash
docker logs beddrop-nginx --tail=100
```

Логи Laravel PHP-FPM:

```bash
docker logs beddrop-app --tail=100
```

Публичные health checks:

```bash
curl -I https://beddrop.ru
curl -i https://api.beddrop.ru/up
curl -i https://api.beddrop.ru/api/ping
```

### Production DB commands

Обычные миграции:

```bash
cd /root/beddrop
docker compose --env-file compose.env -f docker-compose.prod.yml exec -T app php artisan migrate --force
```

Полный сброс production DB с seed:

```bash
cd /root/beddrop
docker compose --env-file compose.env -f docker-compose.prod.yml exec -T app php artisan migrate:fresh --seed --force
```

`migrate:fresh` удаляет текущие production-таблицы. Используйте только осознанно.

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

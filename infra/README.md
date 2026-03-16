# Infra

Папка `infra/` содержит локальную инфраструктуру проекта на базе Docker для запуска backend-приложения.

## Обзор

В каталоге описано окружение для Laravel backend со следующими сервисами:

- `app` - PHP-FPM контейнер, собираемый из `backend/Dockerfile`
- `nginx` - HTTP-вход и прокси до PHP-FPM
- `mysql` - база данных MySQL 8.0
- `redis` - Redis 7 для кэша и очередей
- `queue` - отдельный воркер Laravel очередей

Все сервисы подключены к общей сети `beddrop-network`.

## Структура

```text
infra/
├── .env
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
- публикует порты `8080`, `3306` и `6379`
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

### `.env`

Переменные окружения для инфраструктурных сервисов. В текущем Compose-файле они используются контейнером MySQL:

- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_ROOT_PASSWORD`

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
docker compose -f infra/docker-compose.yml up --build
```

Запуск в фоне:

```bash
docker compose -f infra/docker-compose.yml up -d --build
```

Остановка сервисов:

```bash
docker compose -f infra/docker-compose.yml down
```

Остановка с удалением volumes:

```bash
docker compose -f infra/docker-compose.yml down -v
```

## Настройки backend

Инфраструктура поднимает MySQL и Redis, но Laravel должен быть настроен на эти контейнеры в `backend/.env`.

Типичные значения для локальной Docker-разработки:

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
```

Если очереди должны работать через Redis, это также нужно явно отразить в `backend/.env`.

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
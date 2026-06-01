#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data bootstrap/cache storage public

if [ ! -L public/storage ]; then
    rm -rf public/storage
    gosu www-data php artisan storage:link --force >/dev/null 2>&1 || true
fi

if [ "$1" = "php" ] || [ "$1" = "composer" ]; then
    exec gosu www-data "$@"
fi

exec docker-php-entrypoint "$@"

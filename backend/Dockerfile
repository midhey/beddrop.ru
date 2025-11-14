FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    pkg-config \
    && docker-php-ext-install pdo_mysql mbstring zip gd \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN usermod -u 1000 www-data && \
    groupmod -g 1000 www-data && \
    usermod -d /var/www/www-data-home www-data && \
    mkdir -p /var/www/www-data-home/.config/psysh && \
    chown -R www-data:www-data /var/www/www-data-home

WORKDIR /var/www/html

USER www-data

CMD ["php-fpm"]

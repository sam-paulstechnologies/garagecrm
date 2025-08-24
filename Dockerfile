# Laravel PHP-FPM + Nginx (multi-stage) — production
FROM php:8.2-fpm AS base
RUN apt-get update && apt-get install -y git unzip libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd intl zip \    && pecl install redis \    && docker-php-ext-enable redis
WORKDIR /var/www/html
COPY . /var/www/html
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
FROM nginx:1.27-alpine AS nginx
COPY --from=base /var/www/html/public /var/www/html/public
COPY ./nginx.conf /etc/nginx/conf.d/default.conf

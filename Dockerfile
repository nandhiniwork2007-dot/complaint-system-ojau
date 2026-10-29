FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

FROM php:8.3-apache-bookworm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev \
    && docker-php-ext-install bcmath intl opcache pdo_mysql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY render-entrypoint.sh /usr/local/bin/render-entrypoint
COPY php-production.ini /usr/local/etc/php/conf.d/99-production.ini


RUN chmod +x /usr/local/bin/render-entrypoint \
    && composer dump-autoload --no-dev --optimize --no-scripts \
    && php -r 'require "vendor/autoload.php"; exit(class_exists("Nette\\Schema\\Kind") ? 0 : 1);' \
    && php artisan package:discover --ansi \
    && php artisan view:cache \
    && mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage/framework storage/logs bootstrap/cache \
    && chown www-data:www-data storage/app/private \
    && chmod -R ug+rwX storage/framework storage/logs bootstrap/cache \
    && chmod ug+rwx storage/app/private


EXPOSE 10000

ENTRYPOINT ["render-entrypoint"]

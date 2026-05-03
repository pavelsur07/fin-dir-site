ARG SITE_BASE_IMAGE=vashfindir-site-php-apache-base:8.3-bookworm

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM ${SITE_BASE_IMAGE}

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor

EXPOSE 80

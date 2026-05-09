FROM composer:2.8.5 AS composer-bin

FROM php:8.4-cli-alpine AS vendor
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV TZ=Europe/Moscow
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

RUN set -eux; \
    apk add --no-cache git unzip tzdata icu-libs libzip libpq; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpq-dev libxml2-dev; \
    docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl zip xml bcmath; \
    apk del .build-deps

COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
# --no-scripts используется, потому что Symfony scripts могут требовать runtime env/secrets во время image build.
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-interaction \
    --no-progress \
    --no-scripts

COPY . .
RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative \
    --no-interaction \
    --no-scripts \
 && rm -rf /tmp/cache /root/.composer/cache /home/app/.composer/cache

FROM php:8.4-fpm-alpine

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV TZ=Europe/Moscow

WORKDIR /app

RUN set -eux; \
    apk add --no-cache tzdata icu-libs libzip libpq; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpq-dev libxml2-dev; \
    docker-php-ext-configure opcache --disable-opcache-jit; \
    docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql opcache intl zip xml bcmath; \
    apk del .build-deps

COPY docker/common/php/conf.d/common.ini /usr/local/etc/php/conf.d/zz-common.ini
COPY docker/production/php/conf.d/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/production/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/common/php/fpm/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

RUN addgroup -g 1000 -S app && adduser -u 1000 -S -G app app

COPY --from=vendor --chown=app:app /app /app
RUN mkdir -p /app/var/cache /app/var/log && chown -R app:app /app

EXPOSE 9000
USER app
CMD ["php-fpm", "-F"]

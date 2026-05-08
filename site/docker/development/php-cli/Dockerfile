FROM composer:2.8.5 AS composer-bin
FROM php:8.4-cli-alpine

ENV TZ=Europe/Moscow

WORKDIR /app

RUN set -eux; \
    apk add --no-cache bash coreutils git unzip tzdata icu-libs libzip libpq; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev postgresql-dev; \
    docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql opcache intl zip bcmath; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    apk del .build-deps

COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY docker/common/php/conf.d/common.ini /usr/local/etc/php/conf.d/zz-common.ini
COPY docker/development/php/conf.d/development.ini /usr/local/etc/php/conf.d/zz-development.ini
COPY docker/development/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

RUN addgroup -g 1000 -S app && adduser -u 1000 -S -G app app

USER app
CMD ["sh"]

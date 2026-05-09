FROM php:8.4-fpm-alpine

ENV TZ=Europe/Moscow

WORKDIR /app

RUN set -eux; \
    apk add --no-cache tzdata icu-libs libzip libpq; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev postgresql-dev libxml2-dev; \
    docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql opcache intl zip xml bcmath; \
    apk del .build-deps

COPY docker/common/php/conf.d/common.ini /usr/local/etc/php/conf.d/zz-common.ini
COPY docker/development/php/conf.d/development.ini /usr/local/etc/php/conf.d/zz-development.ini
COPY docker/common/php/fpm/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

RUN addgroup -g 1000 -S app && adduser -u 1000 -S -G app app

EXPOSE 9000
USER app
CMD ["php-fpm", "-F"]

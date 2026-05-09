FROM php:8.4-cli-alpine

ENV TZ=Europe/Moscow

WORKDIR /app

RUN set -eux; \
    apk add --no-cache \
        bash \
        coreutils \
        git \
        unzip \
        tzdata \
        libzip \
        libpq; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libzip-dev \
        libpq-dev; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        zip \
        bcmath; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/pear /tmp/pear-build-*

COPY --from=composer:2.8.5 /usr/bin/composer /usr/local/bin/composer

COPY docker/common/php/conf.d/common.ini /usr/local/etc/php/conf.d/zz-common.ini
COPY docker/development/php/conf.d/development.ini /usr/local/etc/php/conf.d/zz-development.ini

RUN addgroup -g 1000 -S app \
 && adduser -u 1000 -S -G app app

USER app

CMD ["sh"]

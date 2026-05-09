FROM php:8.4-fpm-alpine AS php-base

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV TZ=Europe/Moscow

WORKDIR /app

RUN set -eux; \
    apk add --no-cache \
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
    rm -rf /tmp/pear /tmp/pear-build-*; \
    addgroup -g 1000 -S app; \
    adduser -u 1000 -S -G app app

COPY docker/common/php/conf.d/common.ini /usr/local/etc/php/conf.d/zz-common.ini
COPY docker/production/php/conf.d/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/production/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/common/php/fpm/www.conf /usr/local/etc/php-fpm.d/zz-www.conf


FROM php-base AS vendor

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

COPY --from=composer:2.8.5 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./

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
 && rm -rf /root/.composer/cache /tmp/cache


FROM php-base AS runtime

COPY --from=vendor --chown=app:app /app /app

RUN mkdir -p /app/var/cache /app/var/log \
 && chown -R app:app /app/var

EXPOSE 9000

USER app

CMD ["php-fpm", "-F"]

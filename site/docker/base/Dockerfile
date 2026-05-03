FROM php:8.3-apache-bookworm

RUN set -eux; \
    echo 'Acquire::Retries "5";' > /etc/apt/apt.conf.d/80-retries; \
    echo 'Acquire::http::Timeout "30";' >> /etc/apt/apt.conf.d/80-retries; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev; \
    docker-php-ext-install intl opcache zip; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    a2enmod rewrite; \
    sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf; \
    echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf; \
    a2enconf servername

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 80

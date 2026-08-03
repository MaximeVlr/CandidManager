FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libpq-dev \
        unzip \
    && docker-php-ext-install intl opcache pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/app.ini

ENV COMPOSER_ALLOW_SUPERUSER=1

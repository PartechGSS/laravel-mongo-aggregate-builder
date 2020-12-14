ARG PHP_VERSION=7.4
ARG COMPOSER_VERSION=1.8

FROM composer:${COMPOSER_VERSION}
FROM php:${PHP_VERSION}-cli

RUN apt-get update && \
    apt-get install -y autoconf pkg-config git zlib1g-dev libzip-dev unzip && docker-php-ext-install zip &&\
    pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /code
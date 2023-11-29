ARG PHP_VERSION=""
FROM php:${PHP_VERSION:+${PHP_VERSION}-}fpm-alpine

RUN apk update; \
    apk upgrade; \
    apk add icu-dev;

RUN docker-php-ext-install mysqli; docker-php-ext-install intl

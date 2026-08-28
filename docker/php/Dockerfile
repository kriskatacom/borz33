FROM php:8.4-fpm-alpine

RUN apk add --no-cache icu-dev \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql opcache

WORKDIR /var/www

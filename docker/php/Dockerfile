FROM php:8.4-fpm-alpine

RUN apk add --no-cache icu-dev git unzip \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

WORKDIR /var/www

ENV COMPOSER_ALLOW_SUPERUSER=1

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

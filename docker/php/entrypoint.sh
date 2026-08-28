#!/bin/sh
set -e

if [ -f /var/www/composer.json ] && [ ! -f /var/www/vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

exec docker-php-entrypoint "$@"

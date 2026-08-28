#!/bin/sh
set -e

if [ -f /var/www/composer.json ] && [ ! -f /var/www/vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

uploads="/var/www/api/public/uploads"
mkdir -p "$uploads/products"
# Host bind-mount user (usually 1000) and php-fpm (www-data) both need to write.
chown -R "${HOST_UID:-1000}:www-data" "$uploads"
chmod -R ug+rwX "$uploads"

exec docker-php-entrypoint "$@"

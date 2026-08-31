#!/bin/sh
set -e

if [ -f /var/www/composer.json ] && [ ! -f /var/www/vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

uploads="/var/www/api/public/uploads"
invoices="/var/www/storage/invoices"
mkdir -p "$uploads/products" "$uploads/users" "$uploads/media"
mkdir -p "$invoices"
# Host bind-mount user (usually 1000) and php-fpm (www-data) both need to write.
chown -R "${HOST_UID:-1000}:www-data" "$uploads"
chmod -R ug+rwX "$uploads"
chown -R "${HOST_UID:-1000}:www-data" "$invoices"
chmod -R ug+rwX "$invoices"

exec docker-php-entrypoint "$@"

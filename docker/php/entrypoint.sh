#!/bin/sh
set -e

# Align writable directories for bind-mounted deployments before PHP-FPM starts.
mkdir -p /var/www/storage/logs \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/bootstrap/cache \
    /var/www/plugins

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/plugins
chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache /var/www/plugins

# Fresh packaged installs need to create .env from the web installer.
chown www-data:www-data /var/www
chmod ug+rwx /var/www

if [ ! -f /var/www/.env ] && [ -f /var/www/.env.example ]; then
    cp /var/www/.env.example /var/www/.env
    chown www-data:www-data /var/www/.env
    chmod ug+rw /var/www/.env
fi

exec "$@"

#!/bin/sh
set -e

# La BD SQLite vive en un bind mount del host (./database)
if [ ! -f /var/www/database/database.sqlite ]; then
    touch /var/www/database/database.sqlite
fi

chown -R www-data:www-data /var/www/storage /var/www/database /var/www/bootstrap/cache
chmod -R ug+rwX /var/www/storage /var/www/database /var/www/bootstrap/cache

# Solo el contenedor "app" (php-fpm) ejecuta las migraciones
if [ "$1" = "php-fpm" ]; then
    su-exec www-data php artisan migrate --force
fi

# NUNCA ejecutar "php artisan config:cache" aquí: CodeController lee
# JUDGE_API_URL / JUDGE_API_KEY con env() directamente, y con la config
# cacheada env() devuelve null y se pierde la conexión con Judge0.

exec "$@"

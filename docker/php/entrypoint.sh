#!/bin/sh
set -e

ENV_FILE=/var/www/.env
DB_DIR=/var/www/database
DB_FILE="$DB_DIR/database.sqlite"

# Si falta el .env, Docker crea un DIRECTORIO en su lugar al montar el bind
# mount y el error que sale después es incomprensible. Mejor decirlo claro.
if [ -d "$ENV_FILE" ]; then
    echo "ERROR: /var/www/.env es un directorio." >&2
    echo "       En el host falta el archivo .env; Docker lo creó como carpeta." >&2
    echo "       Solución:  docker compose down && rmdir .env && cp .env.example .env" >&2
    exit 1
fi

# storage/ y bootstrap/cache son de la app entera: ahí sí va un chown -R.
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache

# La BD SQLite vive en un bind mount del host (./database).
#
# OJO: aquí NO va un "chown -R". database/ también contiene migrations/,
# seeders/ y factories/, que son fuentes versionadas; si se le cambia el
# dueño a todo, el usuario del servidor pierde el permiso de escritura y
# el siguiente "git pull" que traiga una migración nueva falla.
#
# En vez de eso: el directorio conserva su dueño del host y se le pone el
# grupo www-data con setgid (SQLite necesita crear el journal aquí, y los
# archivos nuevos heredan el grupo). Lo mismo para el archivo de la BD.
[ -f "$DB_FILE" ] || touch "$DB_FILE"

HOST_UID="$(stat -c %u "$DB_DIR")"
chown "$HOST_UID":www-data "$DB_DIR" "$DB_FILE"
chmod 2775 "$DB_DIR"
chmod 0664 "$DB_FILE"

# Solo el contenedor "app" (php-fpm) inicializa la aplicación. El worker de
# colas trae su propio "command", así que nunca entra aquí.
if [ "$1" = "php-fpm" ]; then
    # APP_KEY: se genera una sola vez y queda escrito en el .env del host.
    # Como root: el .env es un bind mount con el dueño del host y www-data
    # no puede escribirlo. key:generate reescribe el archivo en su sitio,
    # así que el dueño y los permisos no cambian.
    if ! grep -qE '^APP_KEY=.+' "$ENV_FILE"; then
        echo "[entrypoint] Generando APP_KEY..."
        php artisan key:generate --force
    fi

    su-exec www-data php artisan migrate --force
fi

# NUNCA ejecutar "php artisan config:cache" aquí: CodeController lee
# JUDGE_API_URL / JUDGE_API_KEY con env() directamente, y con la config
# cacheada env() devuelve null y se pierde la conexión con Judge0.

exec "$@"

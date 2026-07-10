#!/bin/sh
set -eu

cd /app

if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
    export APP_KEY
fi

database_file="${DB_DATABASE:-/data/database.sqlite}"
database_is_new=false

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p "$(dirname "$database_file")"

    if [ ! -s "$database_file" ]; then
        touch "$database_file"
        database_is_new=true
    fi
fi

php artisan migrate --force

case "${SEED_DATABASE:-true}" in
    1|true|TRUE|yes|YES)
        if [ "$database_is_new" = "true" ]; then
            php artisan db:seed --force
        fi
        ;;
esac

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"

#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if [ "$#" -gt 0 ]; then
  exec "$@"
fi

if [ -z "${APP_KEY:-}" ]; then
  php artisan key:generate --force
fi

# Evita usar config em cache de um arranque antigo (ex.: sqlite) quando o .env real é MySQL
php artisan config:clear || true

php artisan migrate --force
php artisan optimize

exec php artisan serve --host=0.0.0.0 --port=8000

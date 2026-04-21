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

php artisan migrate --force
php artisan optimize

exec php artisan serve --host=0.0.0.0 --port=8000

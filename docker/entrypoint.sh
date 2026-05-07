#!/bin/sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

set_env() {
  key="$1"
  value="$2"

  if [ -z "$value" ]; then
    return
  fi

  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}

set_env APP_ENV "$APP_ENV"
set_env APP_DEBUG "$APP_DEBUG"
set_env APP_URL "$APP_URL"
set_env DB_CONNECTION "$DB_CONNECTION"
set_env DB_HOST "$DB_HOST"
set_env DB_PORT "$DB_PORT"
set_env DB_DATABASE "$DB_DATABASE"
set_env DB_USERNAME "$DB_USERNAME"
set_env DB_PASSWORD "$DB_PASSWORD"
set_env CACHE_STORE "$CACHE_STORE"
set_env QUEUE_CONNECTION "$QUEUE_CONNECTION"
set_env SESSION_DRIVER "$SESSION_DRIVER"
set_env BROADCAST_CONNECTION "$BROADCAST_CONNECTION"

php artisan key:generate --force --no-interaction >/dev/null 2>&1 || true
php artisan jwt:secret --force --no-interaction >/dev/null 2>&1 || true

php artisan config:clear --no-interaction >/dev/null 2>&1 || true
php artisan migrate --force --no-interaction

exec "$@"

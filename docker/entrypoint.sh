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

php artisan key:generate --force --no-interaction >/dev/null 2>&1 || true
php artisan jwt:secret --force --no-interaction >/dev/null 2>&1 || true

php artisan config:clear --no-interaction >/dev/null 2>&1 || true
php artisan migrate --force --no-interaction

exec "$@"

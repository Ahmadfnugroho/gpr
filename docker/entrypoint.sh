#!/bin/sh
set -eu

: "${WEB_AUTOSTART:=true}"
: "${WORKER_AUTOSTART:=false}"
export WEB_AUTOSTART WORKER_AUTOSTART

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "==> migrate"
  php artisan migrate --force --no-interaction
fi

echo "==> clear stale caches"
php artisan optimize:clear >/dev/null 2>&1 || true

echo "==> cache config"
php artisan config:cache

exec "$@"
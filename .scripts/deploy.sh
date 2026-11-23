#!/bin/bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/gpr}"
BRANCH="${BRANCH:-production}"

cd "$APP_DIR"

echo "==> Deploy $BRANCH to $APP_DIR"

git fetch --prune origin
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ponytail: artisan runs as www-data when invoked as root; extend if server user differs
run_artisan() {
  if [ "$(id -u)" -eq 0 ]; then
    sudo -u www-data php artisan "$@"
  else
    php artisan "$@"
  fi
}

run_artisan migrate --force
run_artisan optimize:clear
run_artisan config:cache
run_artisan queue:restart

if [ "$(id -u)" -eq 0 ]; then
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache
fi

echo "==> Deploy done"

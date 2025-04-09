#!/bin/bash
set -e

# Skip cache clearing if cache table doesn't exist
mkdir -p bootstrap/cache
chmod -R 775 bootstrap/cache
composer require livewire/livewire
# Install dependencies
composer install --optimize-autoloader --no-dev

# Build assets
if [ -f "package.json" ]; then
    npm install && npm run build
fi

# Hanya bersihkan cache lokal, tanpa akses database
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan vendor:publish --force --tag=livewire:assets

# Hanya dump autoload
composer dump-autoload
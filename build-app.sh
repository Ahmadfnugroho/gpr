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

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan vendor:publish --force --tag=livewire:assets
php artisan vendor:publish --force --tag=livewire:assets
php artisan filament:assets
php artisan filament:cache-components

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Hanya dump autoload
composer dump-autoload
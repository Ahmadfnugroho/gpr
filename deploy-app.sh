#!/bin/bash
set -e

# Set permission
chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Migrate database
php artisan migrate --force

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link storage
php artisan storage:link || true

# Publish Livewire and Filament assets
php artisan livewire:publish --config
php artisan livewire:publish --assets
php artisan filament:assets
php artisan filament:cache-components

# Optional: dump autoload
composer dump-autoload

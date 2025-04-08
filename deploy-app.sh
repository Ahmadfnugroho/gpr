#!/bin/bash
set -e

# Hapus cache lama
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Buat cache baru
php artisan config:cache
php artisan route:cache
php artisan view:cache

# (Opsional) Optimalkan autoloader
composer dump-autoload

# php artisan route:list | grep livewire
# php artisan storage:link

# php artisan filament:upgrade

php artisan livewire:publish --config
php artisan livewire:publish --assets
php artisan livewire:publish --routes


php artisan migrate --force

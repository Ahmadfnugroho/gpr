#!/bin/bash
set -e

# Skip cache clearing if cache table doesn't exist
mkdir -p bootstrap/cache
chmod -R 775 bootstrap/cache
# Install dependencies
composer install --optimize-autoloader --no-dev

# Build assets
if [ -f "package.json" ]; then
    npm install && npm run build
fi

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
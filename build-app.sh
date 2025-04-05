#!/bin/bash
set -e

# Skip cache clearing if cache table doesn't exist
php artisan tinker --execute="try { DB::select('SELECT 1 FROM cache LIMIT 1'); } catch (\Exception \$e) { exit(0); }" || true

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
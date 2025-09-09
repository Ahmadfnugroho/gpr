#!/bin/bash

# Simple Laravel Production Deployment Script
# Quick and safe script for VPS deployment preparation

set -e

echo "🚀 Laravel Production Deployment - Quick Setup"
echo "=============================================="

# 1. Install production dependencies
echo "📦 Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Build assets (if Vite is configured)
if [ -f "vite.config.js" ] && [ -f "package.json" ]; then
    echo "🎨 Building production assets..."
    if [ ! -d "node_modules" ]; then
        npm ci
    fi
    npm run build
    # Remove node_modules after build
    rm -rf node_modules
    echo "✓ Assets built and node_modules removed"
fi

# 3. Clear and optimize Laravel
echo "⚡ Optimizing Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache  
php artisan view:cache
composer dump-autoload --optimize --no-dev

# 4. Clean up development files
echo "🧹 Removing development files..."

# Remove dev config files
rm -f .editorconfig .php_cs .php_cs.cache .php-cs-fixer.php .php-cs-fixer.cache .styleci.yml
rm -f phpunit.xml pest.php .phpunit.result.cache

# Remove IDE directories
rm -rf .vscode .idea

# Remove tests
rm -rf tests

# Remove documentation (keep README.md)
rm -f CONTRIBUTING.md CHANGELOG.md UPGRADE.md CODE_OF_CONDUCT.md SECURITY.md
rm -f API_DOCUMENTATION.md BUNDLING_AUTO_ASSIGN_IMPLEMENTATION.md
rm -f GPR-SERVER-COMMANDS.md IMPLEMENTATION_SUMMARY.md QUEUE-SETUP.md SQL_ERROR_FIX_SUMMARY.md

# 5. Clean storage
echo "📋 Cleaning storage..."
rm -f storage/logs/*.log
find storage/framework/sessions -type f -name 'sess_*' -delete 2>/dev/null || true
find storage/framework/views -name "*.php" -type f -delete 2>/dev/null || true

# 6. Set permissions
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

echo "✅ Production deployment ready!"
echo ""
echo "📋 Remember to:"
echo "- Set APP_ENV=production in .env"
echo "- Set APP_DEBUG=false in .env" 
echo "- Configure database credentials"
echo "- Run migrations on VPS: php artisan migrate --force"

#!/bin/bash

# Deployment script to fix Filament table structures in production
echo "🚀 Starting Filament table fix deployment..."

# Clear caches
echo "📦 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run the Filament table fix command
echo "🔧 Fixing Filament table structures..."
php artisan filament:fix-tables

# Test the import functionality  
echo "🧪 Testing import functionality..."
php artisan filament:test-import

# Run any pending migrations
echo "📚 Running migrations..."
php artisan migrate --force

echo "✅ Deployment completed!"
echo ""
echo "🔍 To verify the fix worked:"
echo "1. Check if imports table has integer columns (not JSON)"
echo "2. Try using the import feature in Filament admin"
echo "3. Monitor logs for any remaining JSON-related errors"

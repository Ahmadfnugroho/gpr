#!/bin/bash

# 🚀 Global Photo Rental - Production Deployment Script
# This script deploys the latest changes to production server

echo "🚀 Starting GPR Production Deployment..."
echo "======================================="

# Step 1: Put application in maintenance mode
echo "📝 Step 1: Putting application in maintenance mode..."
php artisan down --message="Updating application, please wait..." --retry=60

# Step 2: Pull latest code (if using Git)
echo "📦 Step 2: Pulling latest code..."
# git pull origin main  # Uncomment if using Git

# Step 3: Install/update dependencies
echo "📚 Step 3: Updating dependencies..."
composer install --optimize-autoloader --no-dev

# Step 4: Clear all caches
echo "🧹 Step 4: Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Step 5: Run database migrations
echo "🗄️ Step 5: Running database migrations..."
php artisan migrate --force

# Step 6: Optimize for production
echo "⚡ Step 6: Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 7: Restart PHP-FPM/services (if needed)
echo "🔄 Step 7: Restarting services..."
# sudo systemctl reload php8.2-fpm  # Uncomment if using PHP-FPM
# sudo systemctl reload nginx       # Uncomment if using Nginx

# Step 8: Bring application back online
echo "✅ Step 8: Bringing application back online..."
php artisan up

echo ""
echo "🎉 Deployment completed successfully!"
echo "=================================="
echo "✅ Application is now online with latest updates"
echo "📊 Next: Test application functionality"
echo ""
echo "🔍 Quick health check:"
php artisan migrate:status | tail -5

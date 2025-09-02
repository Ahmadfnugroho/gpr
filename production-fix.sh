#!/bin/bash

# Production Deployment Fix Script for GPR
# This script fixes the Customer model issue and ensures proper deployment

echo "🚀 GPR Production Deployment Fix"
echo "=================================="
echo

# 1. Check current directory and permissions
echo "📍 Current Directory: $(pwd)"
echo "👤 Current User: $(whoami)"
echo

# 2. Check if Customer model exists
echo "🔍 Checking Customer Model..."
if [ -f "app/Models/Customer.php" ]; then
    echo "✅ Customer.php found in app/Models/"
else
    echo "❌ Customer.php NOT found in app/Models/"
    echo "📋 Available models:"
    ls -la app/Models/ | grep -E "\\.php$" | head -10
fi
echo

# 3. Clear all caches first
echo "🧹 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
echo "✅ Caches cleared"
echo

# 4. Check composer autoload
echo "🔄 Refreshing Composer Autoload..."
composer dump-autoload --optimize --no-dev
echo "✅ Composer autoload refreshed"
echo

# 5. Run migrations to ensure database is up to date
echo "🗄️ Running migrations..."
php artisan migrate --force
echo "✅ Migrations completed"
echo

# 6. Check if Shield is properly installed
echo "🛡️ Checking Shield installation..."
php artisan vendor:publish --tag=filament-shield-config --force
echo "✅ Shield config published"
echo

# 7. Install Shield
echo "🔧 Setting up Shield..."
php artisan shield:install --fresh
echo "✅ Shield setup completed"
echo

# 8. Try generating Shield permissions again
echo "🛡️ Generating Shield permissions..."
php artisan shield:generate --all --force || {
    echo "❌ Shield generation failed. Checking issues..."
    echo "🔍 Checking Customer model class..."
    php -r "
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    if (class_exists('App\Models\Customer')) {
        echo '✅ Customer model is loadable via PHP';
    } else {
        echo '❌ Customer model cannot be loaded';
        echo 'Available models in app/Models/: ';
        \$models = glob('app/Models/*.php');
        foreach(\$models as \$model) {
            echo basename(\$model) . ' ';
        }
    }
    echo PHP_EOL;
    "
}
echo

# 9. Check file permissions
echo "🔒 Checking file permissions..."
chmod -R 755 app/
chmod -R 755 config/
chmod -R 755 database/
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
echo "✅ File permissions set"
echo

# 10. Final cache and optimize
echo "⚡ Final optimization..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo "✅ Optimization completed"
echo

# 11. Verify Customer model exists
echo "🔍 Final verification..."
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

echo 'Customer Model Check:' . PHP_EOL;
if (class_exists('App\Models\Customer')) {
    echo '✅ Customer model exists and is loadable' . PHP_EOL;
    try {
        \$customer = new App\Models\Customer();
        echo '✅ Customer model can be instantiated' . PHP_EOL;
    } catch (Exception \$e) {
        echo '❌ Customer model instantiation failed: ' . \$e->getMessage() . PHP_EOL;
    }
} else {
    echo '❌ Customer model still not found' . PHP_EOL;
}
"

echo
echo "🎉 Production fix script completed!"
echo "Now try running: php artisan shield:generate --all"
echo

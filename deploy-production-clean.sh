#!/bin/bash

# Laravel Production Deployment Script
# This script prepares your Laravel project for production deployment on VPS
# Author: Laravel Deployment Expert
# Date: $(date)

set -e  # Exit on any error

echo "🚀 Starting Laravel Production Deployment Preparation..."
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Check if we're in a Laravel project
if [ ! -f "artisan" ] || [ ! -f "composer.json" ]; then
    print_error "This doesn't appear to be a Laravel project directory!"
    exit 1
fi

print_status "Confirmed Laravel project structure"

# Create backup directory with timestamp
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
print_status "Created backup directory: $BACKUP_DIR"

# Step 1: Install production dependencies
echo -e "\n📦 Installing production dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
    print_status "Composer dependencies installed (production only)"
else
    print_error "Composer not found! Please install composer first."
    exit 1
fi

# Step 2: Build assets if using Vite
echo -e "\n🎨 Building production assets..."
if [ -f "vite.config.js" ] && [ -f "package.json" ]; then
    if command -v npm &> /dev/null; then
        # Check if node_modules exists and npm ci if not
        if [ ! -d "node_modules" ]; then
            print_warning "node_modules not found, running npm ci..."
            npm ci
        fi
        
        npm run build
        print_status "Vite assets built for production"
        
        # After building, we can remove node_modules for production
        print_warning "Removing node_modules (not needed in production after build)..."
        rm -rf node_modules/
        print_status "node_modules removed"
    else
        print_warning "npm not found, skipping asset build"
    fi
elif [ -f "webpack.mix.js" ]; then
    if command -v npm &> /dev/null; then
        npm run production
        print_status "Mix assets built for production"
    else
        print_warning "npm not found, skipping asset build"
    fi
else
    print_warning "No Vite or Mix config found, skipping asset build"
fi

# Step 3: Clear all Laravel caches and optimize
echo -e "\n🧹 Clearing caches and optimizing..."
php artisan optimize:clear
print_status "All caches cleared"

php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Production caches generated"

# Step 4: Remove development files and directories
echo -e "\n🗑️  Removing development files..."

# Backup files before deletion (optional)
echo "Creating backup of files to be deleted..."
BACKUP_FILES=(
    ".editorconfig"
    ".php_cs"
    ".php_cs.cache"
    ".php-cs-fixer.php"
    ".php-cs-fixer.cache"
    ".styleci.yml"
    "phpunit.xml"
    "pest.php"
)

for file in "${BACKUP_FILES[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$BACKUP_DIR/" 2>/dev/null || true
    fi
done

# Backup directories
BACKUP_DIRS=(
    "tests"
    ".vscode"
    ".idea"
)

for dir in "${BACKUP_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        cp -r "$dir" "$BACKUP_DIR/" 2>/dev/null || true
    fi
done

print_status "Backup completed"

# Remove development configuration files
DEV_FILES=(
    ".editorconfig"
    ".php_cs"
    ".php_cs.cache" 
    ".php-cs-fixer.php"
    ".php-cs-fixer.cache"
    ".styleci.yml"
    "phpunit.xml"
    "pest.php"
    ".phpunit.result.cache"
)

for file in "${DEV_FILES[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        print_status "Removed $file"
    fi
done

# Remove IDE directories
IDE_DIRS=(
    ".vscode"
    ".idea"
)

for dir in "${IDE_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        rm -rf "$dir"
        print_status "Removed $dir/"
    fi
done

# Remove tests directory
if [ -d "tests" ]; then
    rm -rf "tests"
    print_status "Removed tests/ directory"
fi

# Remove documentation files (keep README.md)
DOC_FILES=(
    "CONTRIBUTING.md"
    "CHANGELOG.md"
    "UPGRADE.md"
    "CODE_OF_CONDUCT.md"
    "SECURITY.md"
    "API_DOCUMENTATION.md"
    "BUNDLING_AUTO_ASSIGN_IMPLEMENTATION.md"
    "GPR-SERVER-COMMANDS.md"
    "IMPLEMENTATION_SUMMARY.md"
    "QUEUE-SETUP.md"
    "SQL_ERROR_FIX_SUMMARY.md"
)

for file in "${DOC_FILES[@]}"; do
    if [ -f "$file" ]; then
        # Backup first
        cp "$file" "$BACKUP_DIR/" 2>/dev/null || true
        rm -f "$file"
        print_status "Removed $file"
    fi
done

# Remove test-related files from root
TEST_FILES=(
    "*.test.php"
    "*Test.php"
)

for pattern in "${TEST_FILES[@]}"; do
    if ls $pattern 1> /dev/null 2>&1; then
        rm -f $pattern
        print_status "Removed test files matching $pattern"
    fi
done

# Step 5: Clean up logs and temporary files
echo -e "\n📋 Cleaning up logs and temporary files..."

# Clear old log files
if [ -d "storage/logs" ]; then
    find storage/logs -name "*.log" -type f -delete
    print_status "Cleared log files"
fi

# Clear session files
if [ -d "storage/framework/sessions" ]; then
    find storage/framework/sessions -type f -name 'sess_*' -delete
    print_status "Cleared session files"
fi

# Clear view cache
if [ -d "storage/framework/views" ]; then
    find storage/framework/views -name "*.php" -type f -delete
    print_status "Cleared compiled views"
fi

# Remove development cache files
DEV_CACHE_FILES=(
    "bootstrap/cache/packages.php"
    "bootstrap/cache/services.php"
)

for file in "${DEV_CACHE_FILES[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        print_status "Removed cached file: $file"
    fi
done

# Step 6: Set proper permissions for production
echo -e "\n🔐 Setting production permissions..."
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
print_status "Permissions set for production"

# Step 7: Optimize autoloader and regenerate caches
echo -e "\n⚡ Final optimizations..."
composer dump-autoload --optimize --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Final optimization completed"

# Step 8: Generate production summary
echo -e "\n📊 Generating deployment summary..."
{
    echo "Laravel Production Deployment Summary"
    echo "Generated on: $(date)"
    echo "=================================="
    echo ""
    echo "Files removed:"
    echo "- Development configuration files (.editorconfig, .php_cs, .styleci.yml, etc.)"
    echo "- IDE directories (.vscode/, .idea/)"
    echo "- Test files and directories (tests/, phpunit.xml, pest.php)"
    echo "- Documentation files (except README.md)"
    echo "- node_modules/ (after asset compilation)"
    echo ""
    echo "Optimizations applied:"
    echo "- Composer autoloader optimized"
    echo "- Production dependencies only"
    echo "- Laravel caches generated (config, routes, views)"
    echo "- Assets compiled for production"
    echo "- Log files cleared"
    echo "- Permissions set correctly"
    echo ""
    echo "Environment requirements:"
    echo "- APP_ENV should be set to 'production'"
    echo "- APP_DEBUG should be set to 'false'"
    echo "- Ensure proper .env file is configured"
} > deployment-summary.txt

print_status "Deployment summary saved to deployment-summary.txt"

# Final checks
echo -e "\n🔍 Final validation checks..."

# Check if artisan works
if php artisan --version > /dev/null 2>&1; then
    print_status "Laravel Artisan is working correctly"
else
    print_error "Laravel Artisan check failed!"
    exit 1
fi

# Check critical directories exist
CRITICAL_DIRS=(
    "app"
    "config"
    "database"
    "public"
    "resources"
    "routes"
    "storage"
    "vendor"
)

for dir in "${CRITICAL_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        print_error "Critical directory missing: $dir"
        exit 1
    fi
done

print_status "All critical directories present"

echo -e "\n🎉 ${GREEN}Production deployment preparation completed successfully!${NC}"
echo "=================================================="
echo ""
echo "📝 Next steps for VPS deployment:"
echo "1. Upload the project to your VPS"
echo "2. Configure your web server (Nginx/Apache)"
echo "3. Set up your .env file with production values"
echo "4. Run: php artisan key:generate (if needed)"
echo "5. Run: php artisan migrate --force (if needed)"
echo "6. Set up SSL certificate"
echo "7. Configure queue workers (if using queues)"
echo ""
echo "⚠️  Important reminders:"
echo "- Backup created in: $BACKUP_DIR"
echo "- Set APP_ENV=production in your .env file"
echo "- Set APP_DEBUG=false in your .env file"
echo "- Configure proper database credentials"
echo "- Set up proper file permissions on VPS"
echo ""
print_status "Ready for production deployment! 🚀"

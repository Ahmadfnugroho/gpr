@echo off
echo ===============================================
echo  Post-Migration Laravel Setup
echo ===============================================

echo [1/7] Clearing all caches...
php artisan optimize:clear

echo [2/7] Ensuring storage directories exist...
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\cache" mkdir "storage\framework\cache"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\app\public" mkdir "storage\app\public"
if not exist "storage\logs" mkdir "storage\logs"

echo [3/7] Setting proper permissions...
icacls "storage" /grant Everyone:F /T /Q 2>nul
icacls "bootstrap\cache" /grant Everyone:F /T /Q 2>nul

echo [4/7] Creating storage symlink...
php artisan storage:link

echo [5/7] Generating application key (if needed)...
php artisan key:generate --ansi

echo [6/7] Caching configurations...
php artisan config:cache

echo [7/7] Running Filament setup...
php artisan filament:upgrade

echo.
echo ===============================================
echo  Post-migration setup complete!
echo  Your application is ready to use.
echo ===============================================
pause

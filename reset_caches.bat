@echo off
echo ===============================================
echo  Laravel Storage & Cache Complete Reset
echo ===============================================

echo [1/12] Stopping any running services...
taskkill /f /im php.exe 2>nul

echo [2/12] Clearing application cache...
php artisan cache:clear

echo [3/12] Clearing configuration cache...
php artisan config:clear

echo [4/12] Clearing route cache...
php artisan route:clear

echo [5/12] Clearing view cache...
php artisan view:clear

echo [6/12] Clearing event cache...
php artisan event:clear

echo [7/12] Clearing compiled views manually...
if exist "storage\framework\views\*.php" del /q "storage\framework\views\*.php"
if exist "storage\framework\views\*.blade.php" del /q "storage\framework\views\*.blade.php"

echo [8/12] Clearing sessions...
if exist "storage\framework\sessions\*" del /q "storage\framework\sessions\*"

echo [9/12] Clearing cache files...
if exist "storage\framework\cache\data" rmdir /s /q "storage\framework\cache\data"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"

echo [10/12] Regenerating optimized files...
php artisan optimize:clear

echo [11/12] Setting proper permissions...
attrib -r "storage\*" /s
icacls "storage" /grant Everyone:F /T /Q 2>nul

echo [12/12] Caching config for performance...
php artisan config:cache

echo.
echo ===============================================
echo  Complete reset finished!
echo  Storage directories cleaned and recreated.
echo  Please restart your browser and test.
echo ===============================================
pause

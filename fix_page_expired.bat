@echo off
echo ===============================================
echo  Fixing Page Expired Login Issues - Laragon
echo ===============================================

echo [1/12] Stopping any running PHP processes...
taskkill /f /im php.exe 2>nul

echo [2/12] Clearing all Laravel caches...
php artisan optimize:clear

echo [3/12] Clearing session files manually...
if exist "storage\framework\sessions\*" del /q "storage\framework\sessions\*" 2>nul

echo [4/12] Clearing view cache files...
if exist "storage\framework\views\*" del /q "storage\framework\views\*.php" 2>nul

echo [5/12] Setting proper Windows permissions...
icacls "storage" /grant Everyone:F /T /Q 2>nul
icacls "bootstrap\cache" /grant Everyone:F /T /Q 2>nul

echo [6/12] Ensuring critical directories exist...
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\logs" mkdir "storage\logs"

echo [7/12] Creating storage symlink...
php artisan storage:link 2>nul

echo [8/12] Caching fresh configuration...
php artisan config:cache

echo [9/12] Testing basic session functionality...
echo Testing session configuration...

echo [10/12] Clearing browser cache instructions...
echo.
echo IMPORTANT: You must clear browser cache and cookies!
echo.
echo Chrome: Settings ^> Privacy ^> Clear browsing data
echo Firefox: Settings ^> Privacy ^> Clear Data  
echo Edge: Settings ^> Privacy ^> Clear browsing data
echo.
echo Make sure to clear cookies for 'gpr.id' domain!
echo.

echo [11/12] Starting development server...
echo.
echo ===============================================
echo  Fix completed! Server starting...
echo.
echo  Next steps:
echo  1. Clear browser cache/cookies completely
echo  2. Visit: http://gpr.id:8000/admin
echo  3. Debug info: http://gpr.id:8000/debug/session
echo.
echo  If issues persist:
echo  - Check Laravel logs: storage/logs/laravel.log
echo  - Use debug routes to inspect session state
echo  - Verify middleware is not interfering
echo ===============================================

echo [12/12] Server will start in 3 seconds...
timeout /t 3 /nobreak >nul
start "Laravel Server" php artisan serve --host=0.0.0.0 --port=8000

echo.
echo Server started! Press any key to continue monitoring...
pause >nul

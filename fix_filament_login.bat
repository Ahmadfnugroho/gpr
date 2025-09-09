@echo off
echo ===============================================
echo  Fixing Filament Login Issues on Laragon
echo ===============================================

echo [1/10] Stopping any running PHP processes...
taskkill /f /im php.exe 2>nul

echo [2/10] Clearing all Laravel caches...
php artisan optimize:clear

echo [3/10] Clearing sessions and views manually...
if exist "storage\framework\sessions\*" del /q "storage\framework\sessions\*"
if exist "storage\framework\views\*" del /q "storage\framework\views\*"

echo [4/10] Setting proper Windows permissions...
icacls "storage" /grant Everyone:F /T /Q 2>nul
icacls "bootstrap\cache" /grant Everyone:F /T /Q 2>nul

echo [5/10] Ensuring critical directories exist...
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\cache" mkdir "storage\framework\cache"

echo [6/10] Creating storage symlink...
php artisan storage:link 2>nul

echo [7/10] Caching fresh configurations...
php artisan config:cache

echo [8/10] Clearing browser cache warning...
echo WARNING: Clear your browser cache and cookies for gpr.id

echo [9/10] Testing configuration...
php test_session.php

echo [10/10] Starting development server...
echo.
echo ===============================================
echo  Fix completed! 
echo  
echo  Next steps:
echo  1. Clear browser cache completely
echo  2. Visit http://gpr.id:8000/admin
echo  3. Login with: imam.prabowo1511@gmail.com
echo  
echo  If issues persist, check the Laravel logs.
echo ===============================================

start "Laravel Server" php artisan serve --host=0.0.0.0 --port=8000
pause

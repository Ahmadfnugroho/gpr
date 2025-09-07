@echo off
echo Starting Laravel Queue Worker...
cd /d "C:\laragon\www\gpr"
php artisan queue:work --tries=3 --timeout=90
pause

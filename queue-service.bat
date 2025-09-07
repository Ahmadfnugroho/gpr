@echo off
REM Laravel Queue Worker as Windows Service
REM Install: sc create "LaravelQueue" binPath="C:\laragon\www\gpr\queue-service.bat" start=auto
REM Start: sc start "LaravelQueue"
REM Stop: sc stop "LaravelQueue"
REM Delete: sc delete "LaravelQueue"

:restart
echo Starting Laravel Queue Worker...
cd /d "C:\laragon\www\gpr"
php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=60
echo Queue worker stopped. Restarting in 5 seconds...
timeout /t 5 /nobreak
goto restart

@echo off
echo ========================================
echo Upload Laravel Queue Files to Ubuntu
echo ========================================
echo.

REM Cek apakah WSL tersedia
where wsl >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: WSL not found! Please install WSL or use Git Bash
    echo.
    echo Alternative options:
    echo 1. Install WSL: https://docs.microsoft.com/en-us/windows/wsl/install
    echo 2. Use Git Bash to run upload-to-ubuntu.sh
    echo 3. Upload files manually using SCP or FTP client
    pause
    exit /b 1
)

echo Using WSL to upload files...
echo.

REM Convert Windows path to WSL path
set "WSL_PATH=/mnt/c/laragon/www/gpr"

REM Run the upload script using WSL
wsl -e bash -c "cd %WSL_PATH% && chmod +x upload-to-ubuntu.sh && ./upload-to-ubuntu.sh"

echo.
echo Upload process completed!
pause

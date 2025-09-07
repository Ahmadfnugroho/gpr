# Laravel Queue Worker Service Installer
# Jalankan sebagai Administrator

Write-Host "Installing Laravel Queue Worker Service..." -ForegroundColor Green

# Path ke batch file
$batchPath = "C:\laragon\www\gpr\queue-service.bat"

# Pastikan file batch ada
if (-not (Test-Path $batchPath)) {
    Write-Host "Error: queue-service.bat not found!" -ForegroundColor Red
    exit 1
}

try {
    # Hapus service lama jika ada
    $existingService = Get-Service -Name "LaravelQueue" -ErrorAction SilentlyContinue
    if ($existingService) {
        Write-Host "Removing existing service..." -ForegroundColor Yellow
        Stop-Service -Name "LaravelQueue" -Force -ErrorAction SilentlyContinue
        sc.exe delete "LaravelQueue"
        Start-Sleep -Seconds 2
    }

    # Install service baru
    Write-Host "Creating new service..." -ForegroundColor Yellow
    sc.exe create "LaravelQueue" binPath="$batchPath" start=auto DisplayName="Laravel Queue Worker"
    
    # Set recovery options
    sc.exe failure "LaravelQueue" reset=86400 actions=restart/5000/restart/5000/restart/5000

    Write-Host "Service installed successfully!" -ForegroundColor Green
    Write-Host "To start the service: sc start LaravelQueue" -ForegroundColor Cyan
    Write-Host "To stop the service: sc stop LaravelQueue" -ForegroundColor Cyan
    
} catch {
    Write-Host "Error installing service: $_" -ForegroundColor Red
}

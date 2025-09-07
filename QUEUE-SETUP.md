# 🚀 Laravel Queue Worker Setup Guide

## ⚡ Solusi Queue Worker Permanen

Karena queue worker akan berhenti jika terminal tertutup, berikut berbagai solusi untuk menjalankannya secara permanen:

## 🪟 **1. Windows Development (Saat ini)**

### Opsi A: Synchronous (Rekomendasi untuk Development)
```bash
# Sudah diset ke sync di .env
QUEUE_CONNECTION=sync
```
✅ Email langsung dikirim tanpa delay

### Opsi B: Windows Service (Untuk Production Windows)
```powershell
# Jalankan sebagai Administrator
./install-queue-service.ps1

# Manual commands:
sc start "LaravelQueue"    # Start service
sc stop "LaravelQueue"     # Stop service
sc delete "LaravelQueue"   # Remove service
```

## 🐧 **2. Linux Server (Production)**

### Opsi A: Systemd Service (Modern Linux)
```bash
# 1. Copy file ke system
sudo cp deployment/laravel-queue.service /etc/systemd/system/

# 2. Update path di file service
sudo nano /etc/systemd/system/laravel-queue.service

# 3. Install dan start
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue

# 4. Monitor status
sudo systemctl status laravel-queue
sudo journalctl -u laravel-queue -f
```

### Opsi B: Supervisor (Traditional Linux)
```bash
# 1. Install supervisor
sudo apt install supervisor

# 2. Copy config
sudo cp deployment/laravel-queue.conf /etc/supervisor/conf.d/

# 3. Update path di config file
sudo nano /etc/supervisor/conf.d/laravel-queue.conf

# 4. Start
sudo supervisorctl reread
sudo supervisorctl update  
sudo supervisorctl start laravel-queue:*

# 5. Monitor
sudo supervisorctl status
sudo tail -f /var/log/supervisor/laravel-queue.log
```

### Opsi C: Cron Job Backup
```bash
# Add to crontab
crontab -e

# Add these lines:
* * * * * cd /path/to/laravel && php artisan schedule:run >> /dev/null 2>&1
*/5 * * * * /path/to/laravel/deployment/check-queue.sh >> /var/log/queue-check.log 2>&1
```

## 📊 **3. Monitoring Queue**

### Real-time Monitor
```bash
# Monitor dalam terminal
php artisan queue:monitor

# Monitor dengan interval custom (default: 5 detik)
php artisan queue:monitor --refresh=10
```

### Manual Check
```bash
# Cek pending jobs
php artisan queue:monitor database

# Cek failed jobs
php artisan queue:failed

# Restart failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## ⚙️ **4. Konfigurasi Queue**

### Development (.env)
```env
QUEUE_CONNECTION=sync    # Immediate processing
```

### Production (.env)
```env
QUEUE_CONNECTION=database    # Queue processing
```

### Queue Settings
```php
// config/queue.php
'database' => [
    'driver' => 'database',
    'table' => 'jobs',
    'queue' => 'default',
    'retry_after' => 300,  # 5 minutes
],
```

## 🔧 **5. Troubleshooting**

### Queue Worker Stuck
```bash
# Restart queue worker
php artisan queue:restart

# Force kill (Linux)
pkill -f "artisan queue:work"
```

### Failed Jobs
```bash
# View failed jobs
php artisan queue:failed

# Retry specific job
php artisan queue:retry 5

# Retry all failed jobs  
php artisan queue:retry all

# Delete failed job
php artisan queue:forget 5
```

### Performance Tuning
```bash
# Multiple workers
php artisan queue:work --queue=high,default --sleep=3 --tries=3 --max-time=3600

# Optimize for memory
php artisan queue:work --memory=128 --timeout=60
```

## 🚀 **6. Rekomendasi Setup**

### Development
- ✅ Gunakan `QUEUE_CONNECTION=sync`
- ✅ Email langsung terkirim
- ✅ Tidak perlu setup tambahan

### Staging/Production  
- ✅ Gunakan `QUEUE_CONNECTION=database`
- ✅ Setup systemd service (Linux) atau Windows Service
- ✅ Setup monitoring dan auto-restart
- ✅ Setup cron backup checker

## 📝 **7. File yang Dibuat**

```
deployment/
├── laravel-queue.service    # Systemd service
├── laravel-queue.conf       # Supervisor config  
├── check-queue.sh          # Auto-restart script
└── queue-cron.txt          # Cron job examples

queue-service.bat           # Windows service script
install-queue-service.ps1   # Windows service installer
start-queue.bat            # Manual Windows starter
```

## ⚡ **Quick Start Commands**

```bash
# Development - immediate emails
php artisan config:cache

# Production - start worker manually  
php artisan queue:work --daemon --tries=3 --timeout=90

# Monitor queue
php artisan queue:monitor
```

---

**💡 Tip**: Untuk development gunakan `sync`, untuk production gunakan `database` + service!

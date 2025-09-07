# 🐧 Laravel Queue Worker Setup untuk Ubuntu Server

## 🚀 Langkah-langkah Lengkap Setup di Ubuntu

### **1. Persiapan Environment (.env)**

```bash
# Di server Ubuntu, edit .env
nano .env

# Ubah dari:
QUEUE_CONNECTION=sync

# Menjadi:
QUEUE_CONNECTION=database
```

### **2. Pastikan Database Queue Tables Ada**

```bash
# Cek apakah table jobs dan failed_jobs sudah ada
php artisan migrate:status

# Jika belum ada, buat migration:
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

### **3. Setup Systemd Service (Rekomendasi)**

```bash
# 1. Upload file service ke server
scp deployment/laravel-queue.service user@your-server:/tmp/

# 2. Login ke server dan copy ke system directory
sudo cp /tmp/laravel-queue.service /etc/systemd/system/

# 3. Edit file untuk menyesuaikan path project Anda
sudo nano /etc/systemd/system/laravel-queue.service
```

**Update path dalam file service:**
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5s
ExecStart=/usr/bin/php /var/www/html/gpr/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=60
WorkingDirectory=/var/www/html/gpr
Environment=HOME=/var/www
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

```bash
# 4. Reload systemd dan enable service
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue

# 5. Start service
sudo systemctl start laravel-queue

# 6. Cek status
sudo systemctl status laravel-queue
```

### **4. Setup Supervisor (Alternative)**

Jika lebih prefer supervisor:

```bash
# 1. Install supervisor
sudo apt update
sudo apt install supervisor

# 2. Upload config file
scp deployment/laravel-queue.conf user@your-server:/tmp/

# 3. Copy ke supervisor directory
sudo cp /tmp/laravel-queue.conf /etc/supervisor/conf.d/

# 4. Edit path dalam config
sudo nano /etc/supervisor/conf.d/laravel-queue.conf
```

**Update config file:**
```ini
[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/gpr/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/html/gpr
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-queue.log
stopwaitsecs=3600
priority=999
```

```bash
# 5. Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# 6. Start queue worker
sudo supervisorctl start laravel-queue:*

# 7. Cek status
sudo supervisorctl status
```

### **5. Setup Cron Job untuk Laravel Scheduler**

```bash
# Edit crontab untuk web user
sudo crontab -e -u www-data

# Tambahkan baris ini:
* * * * * cd /var/www/html/gpr && php artisan schedule:run >> /dev/null 2>&1
```

### **6. Setup Auto-Restart Script (Backup)**

```bash
# 1. Upload script ke server
scp deployment/check-queue.sh user@your-server:/tmp/

# 2. Copy ke project dan buat executable
sudo cp /tmp/check-queue.sh /var/www/html/gpr/
sudo chmod +x /var/www/html/gpr/check-queue.sh

# 3. Edit path dalam script
sudo nano /var/www/html/gpr/check-queue.sh
```

**Update script path:**
```bash
LARAVEL_PATH="/var/www/html/gpr"
```

```bash
# 4. Tambah ke crontab (cek setiap 5 menit)
sudo crontab -e -u www-data

# Tambahkan:
*/5 * * * * /var/www/html/gpr/check-queue.sh >> /var/log/queue-check.log 2>&1
```

### **7. Setup Log Rotation**

```bash
# Buat config logrotate untuk queue logs
sudo nano /etc/logrotate.d/laravel-queue

# Isi dengan:
/var/log/supervisor/laravel-queue.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}

/var/www/html/gpr/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### **8. Permissions & Ownership**

```bash
# Pastikan ownership correct
sudo chown -R www-data:www-data /var/www/html/gpr
sudo chmod -R 755 /var/www/html/gpr
sudo chmod -R 775 /var/www/html/gpr/storage
sudo chmod -R 775 /var/www/html/gpr/bootstrap/cache

# Untuk log files
sudo mkdir -p /var/log/supervisor
sudo chown www-data:www-data /var/log/supervisor
```

## 📊 **Monitoring & Management**

### **Cek Status Queue**
```bash
# Via systemd
sudo systemctl status laravel-queue
sudo journalctl -u laravel-queue -f

# Via supervisor  
sudo supervisorctl status
sudo tail -f /var/log/supervisor/laravel-queue.log

# Via Laravel
cd /var/www/html/gpr
php artisan queue:monitor
```

### **Control Service**
```bash
# Systemd commands
sudo systemctl start laravel-queue
sudo systemctl stop laravel-queue
sudo systemctl restart laravel-queue
sudo systemctl disable laravel-queue

# Supervisor commands
sudo supervisorctl start laravel-queue:*
sudo supervisorctl stop laravel-queue:*
sudo supervisorctl restart laravel-queue:*
```

### **Queue Management**
```bash
cd /var/www/html/gpr

# Cek failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Restart workers (graceful)
php artisan queue:restart
```

## 🔧 **Troubleshooting**

### **Jika Service Tidak Start**
```bash
# Cek log error
sudo journalctl -u laravel-queue -n 50

# Cek permission
ls -la /var/www/html/gpr
sudo chown -R www-data:www-data /var/www/html/gpr

# Test manual
sudo -u www-data php /var/www/html/gpr/artisan queue:work --once
```

### **Jika Queue Stuck**
```bash
# Force restart
sudo systemctl restart laravel-queue

# Manual kill dan restart
sudo pkill -f "artisan queue:work"
sudo systemctl start laravel-queue
```

### **Performance Monitoring**
```bash
# Monitor resource usage
htop
ps aux | grep "queue:work"

# Monitor queue stats
cd /var/www/html/gpr
php artisan queue:monitor --refresh=5
```

## 🎯 **Quick Setup Commands**

```bash
# Upload files ke server
scp deployment/laravel-queue.service user@your-server:/tmp/
scp deployment/check-queue.sh user@your-server:/tmp/

# Setup systemd service
sudo cp /tmp/laravel-queue.service /etc/systemd/system/
sudo nano /etc/systemd/system/laravel-queue.service  # Edit path
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue

# Setup cron
sudo crontab -e -u www-data
# Add: * * * * * cd /var/www/html/gpr && php artisan schedule:run >> /dev/null 2>&1

# Setup permissions
sudo chown -R www-data:www-data /var/www/html/gpr
sudo chmod -R 775 /var/www/html/gpr/storage

# Test
sudo systemctl status laravel-queue
cd /var/www/html/gpr && php artisan queue:monitor
```

---

**✅ Setelah setup ini, queue worker akan:**
- Auto-start saat server boot
- Auto-restart jika crash
- Process email dan jobs secara background
- Monitored via systemd/supervisor logs

# 🚀 GPR Server Queue Setup - Langkah Cepat

## 📋 Informasi Server Anda:
- **Server:** srv978232
- **Project Path:** `/var/www/gpr`
- **Current Queue:** `QUEUE_CONNECTION=sync` 
- **Domain:** `admin.globalphotorental.com`

## ⚡ Setup Queue Worker (Copy & Paste Commands):

### **1. Copy Deployment Script ke Server:**

```bash
# Jalankan di server (Anda sudah login sebagai root):
cd /var/www/gpr

# Create deployment script
cat > gpr-deploy.sh << 'EOF'
#!/bin/bash

echo "🐧 Laravel Queue Worker Setup - GPR Server"
echo "========================================"

PROJECT_PATH="/var/www/gpr"
WEB_USER="www-data"

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backed up .env file"

# Update QUEUE_CONNECTION
sed -i 's/QUEUE_CONNECTION=sync/QUEUE_CONNECTION=database/g' .env
echo "✅ Updated QUEUE_CONNECTION to database"

# Setup queue tables
cd $PROJECT_PATH
sudo -u $WEB_USER php artisan queue:table --quiet 2>/dev/null || echo "Queue table exists"
sudo -u $WEB_USER php artisan queue:failed-table --quiet 2>/dev/null || echo "Failed jobs table exists"  
sudo -u $WEB_USER php artisan migrate --force
echo "✅ Database tables ready"

# Cache config
sudo -u $WEB_USER php artisan config:cache
echo "✅ Configuration cached"

# Create systemd service
cat > /etc/systemd/system/laravel-queue.service << 'SERVICEEOF'
[Unit]
Description=Laravel Queue Worker - GPR
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5s
ExecStart=/usr/bin/php /var/www/gpr/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=60
WorkingDirectory=/var/www/gpr
Environment=HOME=/var/www
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
SERVICEEOF

# Enable and start service
systemctl daemon-reload
systemctl enable laravel-queue
systemctl start laravel-queue
echo "✅ Queue service started"

# Setup cron for Laravel scheduler
CRON_ENTRY="* * * * * cd /var/www/gpr && php artisan schedule:run >> /dev/null 2>&1"
(crontab -u www-data -l 2>/dev/null | grep -v "schedule:run"; echo "$CRON_ENTRY") | crontab -u www-data -

# Fix permissions
chown -R www-data:www-data /var/www/gpr
chmod -R 775 /var/www/gpr/storage
echo "✅ Permissions fixed"

# Final check
sleep 2
if systemctl is-active --quiet laravel-queue; then
    echo "🎉 SUCCESS: Queue worker is running!"
    echo ""
    echo "📊 Status Commands:"
    echo "  sudo systemctl status laravel-queue"
    echo "  cd /var/www/gpr && php artisan queue:monitor"
    echo ""
    echo "✅ Email verification will now work immediately!"
else
    echo "❌ ERROR: Queue service failed to start"
    journalctl -u laravel-queue -n 5 --no-pager
fi
EOF

chmod +x gpr-deploy.sh
```

### **2. Jalankan Deployment:**

```bash
# Masih di server sebagai root:
sudo bash gpr-deploy.sh
```

### **3. Monitoring & Test:**

```bash
# Cek status queue worker:
sudo systemctl status laravel-queue

# Monitor real-time:
cd /var/www/gpr && php artisan queue:monitor

# Test email (optional):
cd /var/www/gpr && php artisan tinker
# Dalam tinker ketik:
# \App\Models\User::first()->sendEmailVerificationNotification();
# exit
```

## 🔧 **Management Commands:**

```bash
# Control service:
sudo systemctl start laravel-queue     # Start
sudo systemctl stop laravel-queue      # Stop  
sudo systemctl restart laravel-queue   # Restart
sudo systemctl status laravel-queue    # Status

# View logs:
sudo journalctl -u laravel-queue -f    # Real-time logs
sudo journalctl -u laravel-queue -n 20 # Last 20 lines

# Laravel queue commands:
cd /var/www/gpr
php artisan queue:monitor              # Monitor
php artisan queue:restart              # Graceful restart
php artisan queue:failed               # Show failed jobs
```

## ✅ **Hasil Setelah Setup:**

- ❌ **Sebelum:** `QUEUE_CONNECTION=sync` - email delay 5+ menit
- ✅ **Setelah:** `QUEUE_CONNECTION=database` + queue worker - email instant
- ✅ **Auto-start** saat server reboot
- ✅ **Auto-restart** jika worker crash
- ✅ **Monitoring** via systemd logs

## 🆘 **Troubleshooting:**

```bash
# Jika service gagal start:
sudo journalctl -u laravel-queue -n 10

# Reset permissions:
sudo chown -R www-data:www-data /var/www/gpr
sudo chmod -R 775 /var/www/gpr/storage

# Manual test:
cd /var/www/gpr
sudo -u www-data php artisan queue:work --once
```

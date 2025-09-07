cd /var/www/gpr

cat > gpr-deploy.sh << 'EOF'
#!/bin/bash

echo "🐧 Setting up Queue Worker for GPR..."

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update queue connection 
sed -i 's/QUEUE_CONNECTION=sync/QUEUE_CONNECTION=database/g' .env

# Setup database tables
sudo -u www-data php artisan queue:table --quiet 2>/dev/null || echo "Table exists"
sudo -u www-data php artisan queue:failed-table --quiet 2>/dev/null || echo "Failed table exists"
sudo -u www-data php artisan migrate --force

# Cache config
sudo -u www-data php artisan config:cache

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
ExecStart=/usr/bin/php /var/www/gpr/artisan queue:work --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/gpr
Environment=HOME=/var/www

[Install]
WantedBy=multi-user.target
SERVICEEOF

# Start service
systemctl daemon-reload
systemctl enable laravel-queue
systemctl start laravel-queue

# Setup cron
echo "* * * * * cd /var/www/gpr && php artisan schedule:run >> /dev/null 2>&1" | crontab -u www-data -

# Fix permissions
chown -R www-data:www-data /var/www/gpr
chmod -R 775 /var/www/gpr/storage

echo "✅ Done! Checking status..."
systemctl status laravel-queue
EOF

chmod +x gpr-deploy.sh
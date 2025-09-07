#!/bin/bash

# Laravel Queue Worker Ubuntu Deployment Script
# Jalankan di server Ubuntu sebagai sudo

set -e  # Exit on any error

echo "🐧 Laravel Queue Worker Ubuntu Deployment"
echo "========================================="

# Configuration
PROJECT_PATH="/var/www/html/gpr"
WEB_USER="www-data"
WEB_GROUP="www-data"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   log_error "This script must be run as root (use sudo)"
   exit 1
fi

# Verify project path
if [ ! -d "$PROJECT_PATH" ]; then
    log_error "Project path $PROJECT_PATH not found!"
    log_info "Please update PROJECT_PATH in this script"
    exit 1
fi

log_info "Starting deployment for: $PROJECT_PATH"

# 1. Update .env for production queue
log_info "Step 1: Updating .env for queue configuration..."
cd "$PROJECT_PATH"
if grep -q "QUEUE_CONNECTION=sync" .env; then
    sed -i 's/QUEUE_CONNECTION=sync/QUEUE_CONNECTION=database/g' .env
    log_info "✅ Updated QUEUE_CONNECTION to database"
else
    log_info "✅ QUEUE_CONNECTION already configured"
fi

# 2. Ensure queue tables exist
log_info "Step 2: Setting up queue database tables..."
sudo -u $WEB_USER php artisan queue:table --quiet 2>/dev/null || log_warn "Queue table might already exist"
sudo -u $WEB_USER php artisan queue:failed-table --quiet 2>/dev/null || log_warn "Failed jobs table might already exist"
sudo -u $WEB_USER php artisan migrate --force

# 3. Cache configuration
log_info "Step 3: Caching configuration..."
sudo -u $WEB_USER php artisan config:cache
sudo -u $WEB_USER php artisan route:cache
sudo -u $WEB_USER php artisan view:cache

# 4. Setup systemd service
log_info "Step 4: Setting up systemd service..."
cat > /etc/systemd/system/laravel-queue.service << EOF
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=$WEB_USER
Group=$WEB_GROUP
Restart=always
RestartSec=5s
ExecStart=/usr/bin/php $PROJECT_PATH/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=60
WorkingDirectory=$PROJECT_PATH
Environment=HOME=/var/www
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

# 5. Enable and start service
systemctl daemon-reload
systemctl enable laravel-queue
systemctl start laravel-queue

# 6. Setup Laravel scheduler cron
log_info "Step 5: Setting up Laravel scheduler..."
CRON_ENTRY="* * * * * cd $PROJECT_PATH && php artisan schedule:run >> /dev/null 2>&1"
(crontab -u $WEB_USER -l 2>/dev/null | grep -v "schedule:run"; echo "$CRON_ENTRY") | crontab -u $WEB_USER -

# 7. Setup log rotation
log_info "Step 6: Setting up log rotation..."
cat > /etc/logrotate.d/laravel-queue << EOF
$PROJECT_PATH/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 $WEB_USER $WEB_GROUP
}
EOF

# 8. Fix permissions
log_info "Step 7: Setting proper permissions..."
chown -R $WEB_USER:$WEB_GROUP "$PROJECT_PATH"
chmod -R 755 "$PROJECT_PATH"
chmod -R 775 "$PROJECT_PATH/storage"
chmod -R 775 "$PROJECT_PATH/bootstrap/cache"

# 9. Create monitoring script
log_info "Step 8: Creating queue monitoring script..."
cat > "$PROJECT_PATH/queue-check.sh" << EOF
#!/bin/bash
# Queue worker health check script

if ! systemctl is-active --quiet laravel-queue; then
    echo "\$(date): Queue worker is not running, restarting..." >> /var/log/queue-health.log
    systemctl start laravel-queue
else
    echo "\$(date): Queue worker is running" >> /var/log/queue-health.log
fi
EOF

chmod +x "$PROJECT_PATH/queue-check.sh"

# Add health check to cron (every 5 minutes)
HEALTH_CRON="*/5 * * * * $PROJECT_PATH/queue-check.sh"
(crontab -u root -l 2>/dev/null | grep -v "queue-check.sh"; echo "$HEALTH_CRON") | crontab -u root -

# 10. Final checks
log_info "Step 9: Running final checks..."
sleep 2

if systemctl is-active --quiet laravel-queue; then
    log_info "✅ Laravel Queue service is running"
else
    log_error "❌ Laravel Queue service failed to start"
    journalctl -u laravel-queue -n 10 --no-pager
    exit 1
fi

# Test queue
cd "$PROJECT_PATH"
if sudo -u $WEB_USER php artisan queue:monitor database | grep -q "OK"; then
    log_info "✅ Queue database connection working"
else
    log_warn "⚠️  Queue database connection might have issues"
fi

echo ""
echo "🎉 Deployment completed successfully!"
echo ""
echo "📊 Status & Monitoring Commands:"
echo "  sudo systemctl status laravel-queue"
echo "  sudo journalctl -u laravel-queue -f"
echo "  cd $PROJECT_PATH && php artisan queue:monitor"
echo ""
echo "🔧 Management Commands:"
echo "  sudo systemctl start laravel-queue"
echo "  sudo systemctl stop laravel-queue"  
echo "  sudo systemctl restart laravel-queue"
echo "  cd $PROJECT_PATH && php artisan queue:restart"
echo ""
log_info "Queue worker is now running and will auto-start on server reboot!"

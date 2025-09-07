#!/bin/bash

# Script untuk upload deployment files ke Ubuntu server
# Run this from your local machine

# Configuration - GANTI SESUAI SERVER ANDA
SERVER_USER="root"
SERVER_HOST="168.231.118.190"
SERVER_PATH="/var/www/html/gpr"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

echo "📤 Upload Deployment Files to Ubuntu Server"
echo "==========================================="

# Cek apakah konfigurasi sudah diupdate
if [[ "$SERVER_USER" == "your-username" || "$SERVER_HOST" == "your-server-ip" ]]; then
    log_error "Please update SERVER_USER and SERVER_HOST in this script first!"
    echo "Edit this file and set:"
    echo "  SERVER_USER=\"your-actual-username\""  
    echo "  SERVER_HOST=\"your-actual-server-ip-or-domain\""
    exit 1
fi

log_info "Uploading files to $SERVER_USER@$SERVER_HOST:$SERVER_PATH"

# Test SSH connection
log_info "Testing SSH connection..."
if ! ssh -o ConnectTimeout=10 "$SERVER_USER@$SERVER_HOST" "echo 'SSH connection successful'"; then
    log_error "Cannot connect to server. Please check:"
    echo "  - SSH key is set up"
    echo "  - Server IP/hostname is correct"
    echo "  - Username is correct"
    exit 1
fi

# Create deployment directory on server
log_info "Creating deployment directory on server..."
ssh "$SERVER_USER@$SERVER_HOST" "mkdir -p $SERVER_PATH/deployment"

# Upload deployment files
log_info "Uploading deployment files..."

# Upload systemd service file
scp deployment/laravel-queue.service "$SERVER_USER@$SERVER_HOST:/tmp/"
log_info "✅ Uploaded systemd service file"

# Upload supervisor config (backup option)
scp deployment/laravel-queue.conf "$SERVER_USER@$SERVER_HOST:/tmp/"
log_info "✅ Uploaded supervisor config"

# Upload auto-restart script
scp deployment/check-queue.sh "$SERVER_USER@$SERVER_HOST:/tmp/"
log_info "✅ Uploaded auto-restart script"

# Upload deployment script
scp deployment/ubuntu-deploy.sh "$SERVER_USER@$SERVER_HOST:/tmp/"
ssh "$SERVER_USER@$SERVER_HOST" "chmod +x /tmp/ubuntu-deploy.sh"
log_info "✅ Uploaded deployment script"

# Upload documentation
scp UBUNTU-QUEUE-SETUP.md "$SERVER_USER@$SERVER_HOST:$SERVER_PATH/"
log_info "✅ Uploaded documentation"

echo ""
log_info "🎉 All files uploaded successfully!"
echo ""
echo "📋 Next steps on your Ubuntu server:"
echo ""
echo "1. Connect to your server:"
echo "   ssh $SERVER_USER@$SERVER_HOST"
echo ""
echo "2. Update project path in deployment script (if needed):"
echo "   nano /tmp/ubuntu-deploy.sh"
echo "   # Change PROJECT_PATH if your Laravel project is not in /var/www/html/gpr"
echo ""
echo "3. Run the deployment script:"
echo "   sudo bash /tmp/ubuntu-deploy.sh"
echo ""
echo "4. Monitor the queue worker:"
echo "   sudo systemctl status laravel-queue"
echo "   cd $SERVER_PATH && php artisan queue:monitor"
echo ""
echo "📖 Full documentation is available at:"
echo "   $SERVER_PATH/UBUNTU-QUEUE-SETUP.md"

#!/bin/bash

# Quick Fix Script for 504 Gateway Timeout on Ubuntu + Nginx + PHP-FPM
# IMPORTANT: Backup your configs before running this script!
#
# Usage: 
#   bash quick_fix_ubuntu_timeouts.sh
#   bash quick_fix_ubuntu_timeouts.sh --extreme    (for very large files)

echo "🚀 QUICK FIX FOR 504 GATEWAY TIMEOUT - Ubuntu + Nginx + PHP-FPM"
echo "================================================================="

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check if running as root/sudo
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root (use sudo)${NC}"
   exit 1
fi

# Parse arguments
EXTREME_MODE=false
if [[ "$1" == "--extreme" ]]; then
    EXTREME_MODE=true
    echo -e "${YELLOW}⚠️  EXTREME MODE ENABLED - Very high timeout values${NC}"
    echo
fi

# Set timeout values based on mode
if [ "$EXTREME_MODE" = true ]; then
    NGINX_TIMEOUT="900s"      # 15 minutes
    PHP_TIMEOUT="900"         # 15 minutes  
    MEMORY_LIMIT="2G"
    BODY_SIZE="50M"
    echo -e "${YELLOW}Using EXTREME values: 15min timeout, 2GB memory, 50MB uploads${NC}"
else
    NGINX_TIMEOUT="300s"      # 5 minutes
    PHP_TIMEOUT="300"         # 5 minutes
    MEMORY_LIMIT="1G" 
    BODY_SIZE="20M"
    echo -e "${BLUE}Using STANDARD values: 5min timeout, 1GB memory, 20MB uploads${NC}"
fi

echo
echo "🔍 Detecting system configuration..."

# Find PHP version
PHP_VERSION=$(php -v 2>/dev/null | head -n 1 | grep -o 'PHP [0-9]\.[0-9]' | grep -o '[0-9]\.[0-9]' || echo "")
if [ -z "$PHP_VERSION" ]; then
    echo -e "${RED}❌ PHP not found or not in PATH${NC}"
    echo "Please ensure PHP is installed and accessible"
    exit 1
fi

echo -e "${GREEN}✓${NC} Found PHP version: $PHP_VERSION"

# Create backup directory
BACKUP_DIR="/root/nginx-php-config-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"
echo -e "${BLUE}📁 Backup directory: $BACKUP_DIR${NC}"

echo
echo "🔧 STEP 1: Configuring Nginx..."

# Backup and configure nginx.conf
NGINX_CONF="/etc/nginx/nginx.conf"
if [ -f "$NGINX_CONF" ]; then
    cp "$NGINX_CONF" "$BACKUP_DIR/nginx.conf.backup"
    echo -e "${GREEN}✓${NC} Backed up $NGINX_CONF"
    
    # Check if our settings already exist
    if ! grep -q "# Laravel Import Optimization" "$NGINX_CONF"; then
        # Add settings to http block
        sed -i '/http {/a\\n    # Laravel Import Optimization - Added by quick_fix script\n    client_max_body_size '$BODY_SIZE';\n    client_body_timeout '$NGINX_TIMEOUT';\n    client_header_timeout '$NGINX_TIMEOUT';\n    \n    fastcgi_connect_timeout '$NGINX_TIMEOUT';\n    fastcgi_send_timeout '$NGINX_TIMEOUT';\n    fastcgi_read_timeout '$NGINX_TIMEOUT';\n    fastcgi_buffer_size 128k;\n    fastcgi_buffers 8 128k;\n    fastcgi_busy_buffers_size 256k;\n    fastcgi_temp_file_write_size 256k;\n' "$NGINX_CONF"
        
        echo -e "${GREEN}✓${NC} Added timeout settings to nginx.conf"
    else
        echo -e "${YELLOW}⚠️${NC} Settings already exist in nginx.conf"
    fi
else
    echo -e "${RED}❌ nginx.conf not found at $NGINX_CONF${NC}"
fi

# Configure site configs
SITES_DIR="/etc/nginx/sites-available"
if [ -d "$SITES_DIR" ]; then
    for site in "$SITES_DIR"/*; do
        if [ -f "$site" ] && [ "$(basename "$site")" != "default" ]; then
            SITE_NAME=$(basename "$site")
            cp "$site" "$BACKUP_DIR/${SITE_NAME}.backup"
            
            # Check if this site has PHP location block
            if grep -q "location.*\.php" "$site"; then
                echo -e "${BLUE}Configuring site: $SITE_NAME${NC}"
                
                # Add client_max_body_size to server block if not exists
                if ! grep -q "client_max_body_size" "$site"; then
                    sed -i '/server {/a\\n    # Laravel Import - Large file support\n    client_max_body_size '$BODY_SIZE';\n' "$site"
                fi
                
                # Add FastCGI timeout settings to PHP location block
                if ! grep -q "# Laravel FastCGI timeouts" "$site"; then
                    sed -i '/location ~ \.php$ {/a\\n        # Laravel FastCGI timeouts\n        fastcgi_read_timeout '$NGINX_TIMEOUT';\n        fastcgi_send_timeout '$NGINX_TIMEOUT';\n        fastcgi_connect_timeout '$NGINX_TIMEOUT';\n        fastcgi_buffer_size 128k;\n        fastcgi_buffers 8 128k;\n        fastcgi_busy_buffers_size 256k;\n' "$site"
                fi
                
                echo -e "${GREEN}✓${NC} Updated $SITE_NAME"
            fi
        fi
    done
fi

echo
echo "🔧 STEP 2: Configuring PHP-FPM..."

# Configure PHP-FPM pool
FMP_POOL="/etc/php/$PHP_VERSION/fpm/pool.d/www.conf"
if [ -f "$FMP_POOL" ]; then
    cp "$FMP_POOL" "$BACKUP_DIR/www.conf.backup"
    echo -e "${GREEN}✓${NC} Backed up $FMP_POOL"
    
    # Add our settings if they don't exist
    if ! grep -q "Laravel Import Optimization" "$FMP_POOL"; then
        cat >> "$FMP_POOL" << EOF

; Laravel Import Optimization - Added by quick_fix script
request_terminate_timeout = $PHP_TIMEOUT
php_admin_value[memory_limit] = $MEMORY_LIMIT
php_admin_value[max_execution_time] = $PHP_TIMEOUT
php_admin_value[post_max_size] = $BODY_SIZE
php_admin_value[upload_max_filesize] = $BODY_SIZE
php_admin_value[max_file_uploads] = 20
php_admin_value[max_input_vars] = 5000
EOF
        echo -e "${GREEN}✓${NC} Added settings to PHP-FPM pool config"
    else
        echo -e "${YELLOW}⚠️${NC} Settings already exist in PHP-FPM pool config"
    fi
else
    echo -e "${RED}❌ PHP-FMP pool config not found at $FMP_POOL${NC}"
fi

# Configure php.ini
PHP_INI="/etc/php/$PHP_VERSION/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    cp "$PHP_INI" "$BACKUP_DIR/php.ini.backup"
    echo -e "${GREEN}✓${NC} Backed up $PHP_INI"
    
    # Update php.ini settings
    sed -i "s/^max_execution_time = .*/max_execution_time = $PHP_TIMEOUT/" "$PHP_INI"
    sed -i "s/^memory_limit = .*/memory_limit = $MEMORY_LIMIT/" "$PHP_INI"
    sed -i "s/^post_max_size = .*/post_max_size = $BODY_SIZE/" "$PHP_INI"
    sed -i "s/^upload_max_filesize = .*/upload_max_filesize = $BODY_SIZE/" "$PHP_INI"
    sed -i "s/^max_file_uploads = .*/max_file_uploads = 20/" "$PHP_INI"
    sed -i "s/^max_input_vars = .*/max_input_vars = 5000/" "$PHP_INI"
    
    echo -e "${GREEN}✓${NC} Updated php.ini settings"
else
    echo -e "${RED}❌ php.ini not found at $PHP_INI${NC}"
fi

echo
echo "🔧 STEP 3: Testing and restarting services..."

# Test nginx configuration
echo "Testing nginx configuration..."
if nginx -t; then
    echo -e "${GREEN}✓${NC} Nginx configuration is valid"
    
    # Restart services
    echo "Restarting nginx..."
    systemctl restart nginx
    if systemctl is-active nginx >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Nginx restarted successfully${NC}"
    else
        echo -e "${RED}❌ Failed to restart nginx${NC}"
    fi
    
    echo "Restarting PHP-FPM..."
    systemctl restart php$PHP_VERSION-fmp
    if systemctl is-active php$PHP_VERSION-fpm >/dev/null 2>&1; then
        echo -e "${GREEN}✅ PHP-FPM restarted successfully${NC}"
    else
        echo -e "${RED}❌ Failed to restart PHP-FPM${NC}"
    fi
    
else
    echo -e "${RED}❌ Nginx configuration test failed!${NC}"
    echo -e "${YELLOW}💡 Restoring backup...${NC}"
    
    # Restore backup
    if [ -f "$BACKUP_DIR/nginx.conf.backup" ]; then
        cp "$BACKUP_DIR/nginx.conf.backup" "$NGINX_CONF"
        echo -e "${GREEN}✓${NC} Restored nginx.conf from backup"
    fi
    
    echo -e "${RED}Please check the configuration manually${NC}"
    exit 1
fi

echo
echo "📊 VERIFICATION"
echo "==============="

echo "Current timeout settings:"
echo "📋 Nginx:"
nginx -T 2>/dev/null | grep -E "(client_max_body_size|fastcgi.*timeout)" | head -10

echo
echo "📋 PHP-FPM Pool:"
grep -E "(request_terminate_timeout|memory_limit|max_execution_time)" "$FMP_POOL" | tail -5

echo
echo "📋 PHP.ini:"
php -i | grep -E "(max_execution_time|memory_limit|post_max_size|upload_max_filesize)" | head -5

echo
echo "🎉 CONFIGURATION COMPLETED!"
echo "==========================="

echo -e "${GREEN}✅ Server configured for large file imports${NC}"
echo
echo "📁 Configuration backups saved to: $BACKUP_DIR"
echo
echo "🧪 TESTING:"
echo "1. Try importing a small file first (< 1MB)"
echo "2. Then test with your 528KB, 2000 rows file"
echo "3. Monitor logs: tail -f /var/log/nginx/error.log"
echo
echo "⚙️  Current limits:"
echo "   • Upload size: $BODY_SIZE"
echo "   • Timeout: $NGINX_TIMEOUT / $PHP_TIMEOUT seconds"
echo "   • Memory: $MEMORY_LIMIT"

if [ "$EXTREME_MODE" = true ]; then
    echo
    echo -e "${YELLOW}⚠️  EXTREME MODE was used - consider reverting to standard values after testing${NC}"
fi

echo
echo -e "${BLUE}💡 If 504 errors persist:${NC}"
echo "1. Check /var/log/nginx/error.log"
echo "2. Check PHP-FPM logs: /var/log/php$PHP_VERSION-fpm.log"  
echo "3. Consider running with --extreme flag"
echo "4. Monitor server resources during import"

echo
echo -e "${GREEN}🚀 Ready to test large file imports!${NC}"

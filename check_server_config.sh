#!/bin/bash

# Server Configuration Checker for 504 Gateway Timeout Issues
# Run: bash check_server_config.sh

echo "🔍 CHECKING SERVER CONFIGURATION FOR 504 TIMEOUT ISSUES"
echo "========================================================"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check if value exists and compare
check_value() {
    local config_file=$1
    local setting=$2
    local recommended=$3
    local description=$4
    
    if [ -f "$config_file" ]; then
        result=$(grep -E "^[[:space:]]*$setting" "$config_file" 2>/dev/null | tail -1)
        if [ -n "$result" ]; then
            echo -e "${GREEN}✓${NC} Found in $config_file:"
            echo "  $result"
            echo "  Recommended: $setting $recommended"
        else
            echo -e "${RED}✗${NC} Missing in $config_file: $setting"
            echo "  Recommended: $setting $recommended ($description)"
        fi
    else
        echo -e "${RED}✗${NC} Config file not found: $config_file"
    fi
    echo
}

echo "1. NGINX CONFIGURATION"
echo "======================"

# Check nginx main config
NGINX_CONF="/etc/nginx/nginx.conf"
if [ -f "$NGINX_CONF" ]; then
    echo -e "${GREEN}✓${NC} Found nginx.conf: $NGINX_CONF"
    
    echo "Current timeout settings:"
    grep -E "(client_max_body_size|client_body_timeout|client_header_timeout|fastcgi.*timeout)" "$NGINX_CONF" 2>/dev/null || echo "  No timeout settings found"
    echo
else
    echo -e "${RED}✗${NC} nginx.conf not found at $NGINX_CONF"
    echo "Try: sudo find /etc -name nginx.conf 2>/dev/null"
    echo
fi

# Check site configs
SITES_DIR="/etc/nginx/sites-available"
if [ -d "$SITES_DIR" ]; then
    echo "Site configurations in $SITES_DIR:"
    for site in "$SITES_DIR"/*; do
        if [ -f "$site" ] && [ "$(basename "$site")" != "default" ]; then
            echo "  - $(basename "$site")"
            
            # Check for PHP location block
            if grep -q "location.*\.php" "$site"; then
                echo "    Has PHP location block"
                fastcgi_timeouts=$(grep -E "fastcgi.*timeout" "$site" 2>/dev/null)
                if [ -n "$fastcgi_timeouts" ]; then
                    echo "    FastCGI timeouts found:"
                    echo "$fastcgi_timeouts" | sed 's/^/      /'
                else
                    echo -e "    ${RED}✗${NC} No FastCGI timeout settings"
                fi
            fi
        fi
    done
    echo
else
    echo -e "${RED}✗${NC} Sites directory not found: $SITES_DIR"
    echo
fi

echo "2. PHP-FPM CONFIGURATION"
echo "========================"

# Find PHP versions
PHP_VERSIONS=$(find /etc/php* -name "www.conf" -type f 2>/dev/null | grep -o 'php[0-9]\.[0-9]' | sort -u)
if [ -n "$PHP_VERSIONS" ]; then
    for version in $PHP_VERSIONS; do
        echo "PHP Version: $version"
        
        # Check FPM pool config
        FPM_POOL="/etc/$version/fpm/pool.d/www.conf"
        if [ -f "$FPM_POOL" ]; then
            echo -e "${GREEN}✓${NC} Found FPM pool config: $FPM_POOL"
            
            echo "Current FPM settings:"
            grep -E "(request_terminate_timeout|memory_limit|max_execution_time|post_max_size|upload_max_filesize)" "$FPM_POOL" 2>/dev/null || echo "  No relevant settings found"
            echo
        fi
        
        # Check php.ini
        PHP_INI="/etc/$version/fpm/php.ini"
        if [ -f "$PHP_INI" ]; then
            echo -e "${GREEN}✓${NC} Found php.ini: $PHP_INI"
            
            echo "Current PHP.ini settings:"
            grep -E "^[[:space:]]*(max_execution_time|memory_limit|post_max_size|upload_max_filesize|max_file_uploads|max_input_vars)" "$PHP_INI" 2>/dev/null || echo "  Default values (not explicitly set)"
            echo
        fi
    done
else
    echo -e "${RED}✗${NC} No PHP-FPM configurations found"
    echo "Try: sudo find /etc -name \"www.conf\" -type f 2>/dev/null"
    echo
fi

echo "3. CURRENT SYSTEM STATUS"
echo "========================"

# Check if services are running
echo "Service Status:"
systemctl is-active nginx >/dev/null 2>&1 && echo -e "${GREEN}✓${NC} Nginx is running" || echo -e "${RED}✗${NC} Nginx is not running"

for version in $PHP_VERSIONS; do
    service_name="$version-fpm"
    systemctl is-active "$service_name" >/dev/null 2>&1 && echo -e "${GREEN}✓${NC} $service_name is running" || echo -e "${RED}✗${NC} $service_name is not running"
done

echo

# Check system resources
echo "System Resources:"
echo "Memory:"
free -h | head -2

echo -e "\nDisk space:"
df -h | grep -E "(Filesystem|/$|/var)"

echo

echo "4. RECOMMENDED FIXES"
echo "===================="

echo -e "${YELLOW}For 504 Gateway Timeout fix, add these to your configs:${NC}"
echo
echo "🔧 Nginx (/etc/nginx/nginx.conf or site config):"
echo "  client_max_body_size 20M;"
echo "  fastcgi_read_timeout 300s;"
echo "  fastcgi_send_timeout 300s;"
echo "  fastcgi_connect_timeout 300s;"
echo
echo "🔧 PHP-FPM pool config:"
echo "  request_terminate_timeout = 300"
echo "  php_admin_value[memory_limit] = 1G"
echo "  php_admin_value[max_execution_time] = 300"
echo
echo "🔧 PHP.ini:"
echo "  max_execution_time = 300"
echo "  memory_limit = 1G"
echo "  post_max_size = 20M"
echo "  upload_max_filesize = 20M"

echo
echo "After making changes, restart services:"
echo "  sudo nginx -t"
echo "  sudo systemctl restart nginx"
echo "  sudo systemctl restart php*-fpm"

echo
echo -e "${GREEN}✅ Configuration check completed!${NC}"
echo -e "${YELLOW}💡 See SERVER_CONFIG_UBUNTU_NGINX_FIX.md for detailed instructions${NC}"

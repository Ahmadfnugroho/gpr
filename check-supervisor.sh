#!/bin/bash

echo "🔍 Supervisor Installation Diagnostic"
echo "====================================="

echo ""
echo "1. Checking if supervisorctl command exists:"
if command -v supervisorctl &> /dev/null; then
    echo "✅ supervisorctl command found at: $(which supervisorctl)"
else
    echo "❌ supervisorctl command not found"
fi

echo ""
echo "2. Checking supervisor service status:"
systemctl is-active supervisor && echo "✅ Supervisor service is active" || echo "❌ Supervisor service is not active"
systemctl is-enabled supervisor && echo "✅ Supervisor service is enabled" || echo "❌ Supervisor service is not enabled"

echo ""
echo "3. Checking supervisor process:"
if pgrep -f supervisord > /dev/null; then
    echo "✅ supervisord process is running"
else
    echo "❌ supervisord process is not running"
fi

echo ""
echo "4. Checking PATH:"
echo "Current PATH: $PATH"

echo ""
echo "5. Manual check with full path:"
if [ -f "/usr/bin/supervisorctl" ]; then
    echo "✅ /usr/bin/supervisorctl exists"
    /usr/bin/supervisorctl version
else
    echo "❌ /usr/bin/supervisorctl does not exist"
fi

echo ""
echo "6. Package installation check:"
dpkg -l | grep supervisor

echo ""
echo "7. Supervisor configuration:"
if [ -f "/etc/supervisor/supervisord.conf" ]; then
    echo "✅ Supervisor config exists"
else
    echo "❌ Supervisor config missing"
fi

echo ""
echo "8. Testing deployment script dependency check manually:"
echo "Running the same check the deployment script uses..."

deps=("mysql" "php" "composer" "supervisor" "nginx")
missing=()

for dep in "${deps[@]}"; do
    if ! command -v "$dep" &> /dev/null; then
        missing+=("$dep")
        echo "❌ Missing: $dep"
    else
        echo "✅ Found: $dep at $(which $dep)"
    fi
done

if [ ${#missing[@]} -ne 0 ]; then
    echo ""
    echo "❌ Missing dependencies: ${missing[*]}"
else
    echo ""
    echo "✅ All dependencies found!"
fi

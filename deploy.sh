#!/bin/bash

echo "🚀 Mulai proses sinkronisasi..."

# Menjadi root untuk operasi yang membutuhkan permission
sudo su <<EOF

echo "📥 Mengambil update dari GitHub..."
cd /var/www/gpr
sudo -u ubuntu git pull origin main

echo "🔐 Mengatur permission storage dan bootstrap/cache..."
chown -R ubuntu:www-data /var/www/gpr
chmod -R 775 /var/www/gpr/storage
chmod -R 775 /var/www/gpr/bootstrap/cache

echo "⚡ Optimisasi konfigurasi Laravel..."
sudo -u ubuntu php artisan optimize:clear
sudo -u ubuntu php artisan optimize
sudo -u ubuntu php artisan view:cache

EOF

echo "✅ Deploy selesai!"
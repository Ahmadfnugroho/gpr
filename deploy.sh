#!/bin/bash

echo "🚀 Mulai proses sinkronisasi..."

echo "📥 Mengambil update dari GitHub..."
cd /var/www/gpr
sudo -u ubuntu git pull origin production

echo "🔐 Mengatur permission dan ownership..."
sudo chown -R ubuntu:www-data /var/www/gpr
sudo find /var/www/gpr -type f -exec chmod 664 {} \;
sudo find /var/www/gpr -type d -exec chmod 775 {} \;
sudo find /var/www/gpr -type d -exec chmod g+s {} \;

echo "⚡ Optimisasi konfigurasi Laravel..."
sudo -u ubuntu php artisan optimize:clear
sudo -u ubuntu php artisan optimize
sudo -u ubuntu php artisan view:cache

echo "✅ Deploy selesai!"

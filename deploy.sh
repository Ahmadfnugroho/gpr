#!/bin/bash

# Deploy script for Laravel app on server
# Jalankan dari direktori project: /var/www/gpr

echo "🚀 Mulai proses sinkronisasi..."

# Masuk ke direktori project
cd /var/www/gpr || exit

# Tarik update terbaru dari GitHub (force sinkron dengan branch production)
echo "📥 Mengambil update dari GitHub..."
git fetch origin
git reset --hard origin/production

# Set file permission (opsional tergantung konfigurasi server)

# Install dependency dengan Composer
echo "⚡ Optimisasi konfigurasi Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Selesai
echo "✅ Deploy selesai!"

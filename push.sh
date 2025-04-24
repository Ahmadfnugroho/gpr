#!/bin/bash

# Push script for Laravel app from local machine
# Jalankan dari direktori project

echo "🚀 Mulai proses sinkronisasi ke GitHub..."

# Prompt untuk input pesan commit
read -p "📝 Masukkan pesan commit: " commitMessage

# Tambahkan perubahan, buat commit, dan push ke GitHub
git add .
git commit -m "$commitMessage"
git push origin production

echo "✅ Push ke GitHub selesai!"

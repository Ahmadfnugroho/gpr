# 🚀 Ubuntu Queue Worker - Quick Start Guide

## 📋 Ringkasan Langkah untuk Server Ubuntu

### **Opsi 1: Auto Setup (Recommended)**

1. **Edit upload script** dengan detail server Anda:
   ```bash
   # Edit file: upload-to-ubuntu.sh
   SERVER_USER="your-username"     # Ganti dengan username server
   SERVER_HOST="your-server-ip"    # Ganti dengan IP/domain server
   ```

2. **Upload files ke server:**
   ```bash
   # Dari Windows (jika ada WSL):
   .\upload-to-ubuntu.bat
   
   # Atau dari Git Bash/Linux:
   ./upload-to-ubuntu.sh
   ```

3. **Login ke server Ubuntu dan jalankan deployment:**
   ```bash
   ssh your-username@your-server-ip
   sudo bash /tmp/ubuntu-deploy.sh
   ```

4. **Selesai!** Queue worker akan auto-start dan email langsung terkirim.

---

### **Opsi 2: Manual Setup**

Jika tidak bisa upload otomatis, copy manual:

1. **Login ke server Ubuntu**
2. **Update .env:**
   ```bash
   nano .env
   # Ubah: QUEUE_CONNECTION=sync
   # Jadi: QUEUE_CONNECTION=database
   ```

3. **Setup database tables:**
   ```bash
   php artisan queue:table
   php artisan queue:failed-table
   php artisan migrate
   ```

4. **Buat systemd service:**
   ```bash
   sudo nano /etc/systemd/system/laravel-queue.service
   ```
   
   Isi dengan (sesuaikan path):
   ```ini
   [Unit]
   Description=Laravel Queue Worker
   After=network.target
   
   [Service]
   User=www-data
   Group=www-data
   Restart=always
   RestartSec=5s
   ExecStart=/usr/bin/php /var/www/html/gpr/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   WorkingDirectory=/var/www/html/gpr
   
   [Install]
   WantedBy=multi-user.target
   ```

5. **Start service:**
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable laravel-queue
   sudo systemctl start laravel-queue
   ```

6. **Setup Laravel scheduler:**
   ```bash
   sudo crontab -e -u www-data
   # Tambah: * * * * * cd /var/www/html/gpr && php artisan schedule:run >> /dev/null 2>&1
   ```

---

## 📊 **Monitoring & Management**

### **Cek Status:**
```bash
sudo systemctl status laravel-queue
cd /var/www/html/gpr && php artisan queue:monitor
```

### **Control Service:**
```bash
sudo systemctl start laravel-queue    # Start
sudo systemctl stop laravel-queue     # Stop  
sudo systemctl restart laravel-queue  # Restart
```

### **View Logs:**
```bash
sudo journalctl -u laravel-queue -f
```

---

## ✅ **Hasil Setelah Setup:**

- ✅ **Email verification langsung terkirim** (tidak ada delay 5 menit)
- ✅ **Queue worker otomatis start saat server boot**
- ✅ **Auto-restart jika queue worker crash**
- ✅ **Monitoring via systemd logs**
- ✅ **Semua jobs diproses di background**

---

## 🔧 **Troubleshooting:**

**Jika service tidak start:**
```bash
sudo journalctl -u laravel-queue -n 20
sudo chown -R www-data:www-data /var/www/html/gpr
```

**Jika email masih delay:**
```bash
# Cek apakah .env sudah benar
grep QUEUE_CONNECTION .env

# Cek apakah service berjalan
sudo systemctl status laravel-queue

# Test manual
php artisan queue:work --once
```

**Need help?** Lihat dokumentasi lengkap di `UBUNTU-QUEUE-SETUP.md`

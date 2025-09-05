# Fix 504 Gateway Timeout - Ubuntu + Nginx + PHP-FPM Configuration

## Problem
504 Gateway Time-out saat import file besar (528KB, 2000 rows) di production server Ubuntu dengan Nginx/1.24.0.

## Root Cause
Server timeout limits terlalu rendah untuk proses import yang membutuhkan waktu lama.

---

## 🔧 SOLUTION 1: Server Configuration (RECOMMENDED)

### 1. Edit Nginx Configuration

```bash
# Edit main nginx config
sudo nano /etc/nginx/nginx.conf
```

Tambahkan di dalam `http` block:
```nginx
http {
    # Increase timeout limits for large file processing
    client_max_body_size 20M;
    client_body_timeout 300s;
    client_header_timeout 300s;
    
    proxy_connect_timeout 300s;
    proxy_send_timeout 300s;
    proxy_read_timeout 300s;
    
    # FastCGI timeout settings
    fastcgi_connect_timeout 300s;
    fastcgi_send_timeout 300s;
    fastcgi_read_timeout 300s;
    fastcgi_buffer_size 128k;
    fastcgi_buffers 8 128k;
    fastcgi_busy_buffers_size 256k;
    fastcgi_temp_file_write_size 256k;
    
    # Other existing configurations...
}
```

### 2. Edit Site-Specific Nginx Configuration

```bash
# Edit your site config (replace 'your-site' with actual config name)
sudo nano /etc/nginx/sites-available/your-site
```

Tambahkan di dalam `server` block:
```nginx
server {
    # Existing configurations...
    
    # Increase limits for this site
    client_max_body_size 20M;
    
    location ~ \.php$ {
        # Existing FastCGI configurations...
        fastcgi_read_timeout 300s;
        fastcgi_send_timeout 300s;
        fastcgi_connect_timeout 300s;
        
        # Buffer settings
        fastcgi_buffer_size 128k;
        fastcgi_buffers 8 128k;
        fastcgi_busy_buffers_size 256k;
        
        # Existing fastcgi_pass, etc...
    }
}
```

### 3. Edit PHP-FPM Configuration

```bash
# Find your PHP-FPM pool config (usually www.conf)
sudo find /etc/php* -name "www.conf" -type f

# Edit the config (adjust path based on your PHP version)
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

Update these settings:
```ini
; Maximum execution time for each script (in seconds)
request_terminate_timeout = 300

; Maximum amount of memory a script may consume
php_admin_value[memory_limit] = 1G

; Maximum execution time of each script
php_admin_value[max_execution_time] = 300

; Maximum size of POST data that PHP will accept
php_admin_value[post_max_size] = 20M

; Maximum allowed size for uploaded files
php_admin_value[upload_max_filesize] = 20M

; Maximum number of files that can be uploaded via a single request
php_admin_value[max_file_uploads] = 20

; Maximum input variables
php_admin_value[max_input_vars] = 5000
```

### 4. Edit PHP.ini (Additional Safety)

```bash
# Find php.ini location
sudo find /etc/php* -name "php.ini" -type f

# Edit php.ini for FPM
sudo nano /etc/php/8.1/fpm/php.ini
```

Update these values:
```ini
; Maximum execution time
max_execution_time = 300

; Memory limit
memory_limit = 1G

; Post max size
post_max_size = 20M

; Upload max filesize  
upload_max_filesize = 20M

; Max file uploads
max_file_uploads = 20

; Max input vars
max_input_vars = 5000

; Default socket timeout
default_socket_timeout = 300
```

### 5. Restart Services

```bash
# Test nginx configuration
sudo nginx -t

# If test passes, restart services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm

# Check service status
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
```

---

## 🔧 SOLUTION 2: Application-Level Optimization (Already Implemented)

Your Laravel app already has these optimizations:

### Progressive Memory & Time Limits
```php
// Files > 10MB: 3GB memory, 30 minutes
// Files > 5MB:  2GB memory, 15 minutes  
// Files > 2MB:  1GB memory, 10 minutes
// Files > 1MB:  512MB memory, 5 minutes
// Files < 1MB:  256MB memory, 2 minutes
```

### Performance Features
- ✅ Bulk insert optimization (1000 records per batch)
- ✅ Memory management with chunking
- ✅ Progressive timeout based on file size
- ✅ Ignore user abort
- ✅ Performance logging
- ✅ No queue dependencies (sync-only)

---

## 🔧 SOLUTION 3: Emergency Quick Fix (If Above Doesn't Work)

If you need immediate fix, add this to your site's nginx config:

```bash
sudo nano /etc/nginx/sites-available/your-site
```

Add these EXTREME timeout values:
```nginx
server {
    # EMERGENCY TIMEOUTS (use temporarily)
    client_max_body_size 50M;
    client_body_timeout 900s;    # 15 minutes
    client_header_timeout 900s;  # 15 minutes
    
    location ~ \.php$ {
        fastcgi_read_timeout 900s;    # 15 minutes
        fastcgi_send_timeout 900s;    # 15 minutes  
        fastcgi_connect_timeout 300s; # 5 minutes
        
        # Your existing FastCGI config...
    }
}
```

Then restart:
```bash
sudo nginx -t && sudo systemctl restart nginx
```

---

## 📊 Testing & Monitoring

### 1. Check Current Limits
```bash
# Check nginx config
sudo nginx -T | grep -i timeout

# Check PHP-FPM config  
sudo grep -r "timeout\|memory\|max_execution" /etc/php/*/fpm/

# Check current PHP settings
php -i | grep -E "(max_execution_time|memory_limit|post_max_size|upload_max_filesize)"
```

### 2. Monitor Import Process
```bash
# Monitor PHP-FPM processes during import
sudo watch "ps aux | grep php-fpm"

# Monitor memory usage
sudo watch "free -h"

# Check Laravel logs
tail -f /path/to/your/laravel/storage/logs/laravel.log
```

### 3. Test Import Performance
The app will log performance metrics:
```
[INFO] Starting customer import: file_size=0.52MB, estimated_rows=2000
[INFO] Customer import completed: execution_time=45.2s, memory_peak=128MB
```

---

## 🚀 PRODUCTION RECOMMENDATIONS

### 1. Recommended Server Specs for Large Imports
```
RAM: Minimum 2GB, Recommended 4GB+
PHP Memory: 1-2GB for large files
CPU: 2+ cores
Storage: SSD recommended
```

### 2. Monitoring Setup
```bash
# Setup log rotation
sudo nano /etc/logrotate.d/laravel

/path/to/laravel/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    sharedscripts
}
```

### 3. Security Considerations
- File upload hanya dari trusted users
- Validasi MIME type yang ketat
- Limit concurrent imports per user
- Regular cleanup of temporary files

---

## ✅ IMPLEMENTATION CHECKLIST

1. **[ ] Update Nginx main config** (`/etc/nginx/nginx.conf`)
2. **[ ] Update site-specific config** (`/etc/nginx/sites-available/your-site`)  
3. **[ ] Update PHP-FPM pool config** (`/etc/php/8.1/fpm/pool.d/www.conf`)
4. **[ ] Update PHP.ini** (`/etc/php/8.1/fpm/php.ini`)
5. **[ ] Test nginx config** (`sudo nginx -t`)
6. **[ ] Restart services** (`nginx` + `php-fpm`)
7. **[ ] Test import with small file first**
8. **[ ] Test import with large file (528KB)**
9. **[ ] Monitor logs during import**
10. **[ ] Verify no 504 errors**

---

## 📞 TROUBLESHOOTING

### Still Getting 504?
1. Check `/var/log/nginx/error.log`
2. Check PHP-FPM error logs: `/var/log/php8.1-fpm.log`
3. Increase timeouts to EXTREME values (900s)
4. Consider using async with supervisor (if needed)

### Performance Issues?
1. Monitor server resources during import
2. Consider breaking large files into smaller chunks
3. Implement progress feedback for users
4. Add file size warnings in UI

### File Too Large?
Current limit: 15MB. To increase:
```php
// In CustomerController, increase validation:
'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:30720', // 30MB
```

**Dengan konfigurasi ini, import file 528KB dengan 2000 rows seharusnya bisa selesai tanpa 504 timeout!**

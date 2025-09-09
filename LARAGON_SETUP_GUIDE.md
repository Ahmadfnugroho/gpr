# 🚀 Laravel 11 + FilamentPHP + Livewire on Laragon Setup Guide

## 🎯 **Problem Solved**
- ✅ **419 Page Expired** errors fixed
- ✅ **Missing view files** resolved  
- ✅ **Proper custom domain** configuration
- ✅ **Production-ready** maintenance scripts

## ⚙️ **Environment Configuration**

### `.env` Settings (Optimized for Laragon)
```env
APP_URL=http://gpr.id
ASSET_URL=http://gpr.id

# Simple session config - no domain binding needed for Laragon
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

# Sanctum for custom domain
SANCTUM_STATEFUL_DOMAINS=gpr.id,localhost,127.0.0.1
```

**Key Points:**
- ❌ **No SESSION_DOMAIN needed** - Laragon handles this
- ❌ **No custom CSRF middleware** - Default Laravel works fine
- ❌ **No custom Livewire service provider** - Unnecessary complexity

## 🛠️ **Maintenance Scripts**

### After `migrate:fresh`:
```bash
./post_migrate.bat
```

### When experiencing cache issues:
```bash
./reset_caches.bat
```

## 📁 **Directory Structure Check**
Ensure these directories exist with proper permissions:
```
storage/
├── framework/
│   ├── cache/
│   ├── sessions/
│   └── views/          ← Must be empty after view:clear
├── app/
│   └── public/
└── logs/
```

## 🔧 **Manual Troubleshooting**

### 1. **View Cache Issues**
```bash
# Clear views completely
php artisan view:clear
Remove-Item "storage/framework/views/*" -Force

# Restart and let Laravel regenerate
```

### 2. **Session Issues**  
```bash
# Clear sessions
Remove-Item "storage/framework/sessions/*" -Force

# Clear browser cookies and cache
```

### 3. **Permission Issues**
```bash
# Set proper Windows permissions
icacls "storage" /grant Everyone:F /T /Q
icacls "bootstrap/cache" /grant Everyone:F /T /Q
```

## 🚦 **Testing Checklist**

### ✅ **Authentication Test**
1. Navigate to `http://gpr.id:8000/admin`
2. Login successfully
3. No 419 errors should appear

### ✅ **Livewire Test**
1. Create/edit records in Filament
2. Use filters and search
3. Check pagination
4. All should work without errors

### ✅ **Network Tab Check**
1. Open DevTools (F12) → Network
2. Perform Livewire actions
3. `/livewire/update` requests should return 200 OK

## 🎯 **Best Practices for Laragon**

### **DO:**
- ✅ Use simple session configuration
- ✅ Include custom domain in SANCTUM_STATEFUL_DOMAINS
- ✅ Run maintenance scripts after major changes
- ✅ Clear browser cache when testing

### **DON'T:**
- ❌ Create custom CSRF middleware for Laragon
- ❌ Override Livewire service providers unnecessarily  
- ❌ Set SESSION_DOMAIN unless absolutely needed
- ❌ Ignore storage permissions on Windows

## 🔄 **Workflow: After Fresh Migration**

1. **Run migration:**
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Run post-setup:**
   ```bash
   ./post_migrate.bat
   ```

3. **Start development:**
   ```bash
   php artisan serve
   # Visit: http://gpr.id:8000/admin
   ```

4. **If issues arise:**
   ```bash
   ./reset_caches.bat
   ```

## 🎉 **Production Notes**

For production deployment:
- Remove `.bat` files from production
- Use database sessions instead of file sessions
- Set proper environment-specific domains in `SANCTUM_STATEFUL_DOMAINS`
- Enable HTTPS and set `SESSION_SECURE_COOKIE=true`

---

## 📞 **Support**

If you encounter issues:
1. Run `./reset_caches.bat` first
2. Check `storage/logs/laravel.log` for errors
3. Ensure Laragon is properly serving `gpr.id`
4. Clear browser cache completely

**This setup is production-ready and follows Laravel/Filament best practices!** 🚀

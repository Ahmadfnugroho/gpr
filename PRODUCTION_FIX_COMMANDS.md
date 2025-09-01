# 🔧 **PRODUCTION SERVER - ERROR FIXES**

## 🎯 **IMMEDIATE ACTIONS NEEDED ON PRODUCTION SERVER**

### **📱 Connect to your production server:**
```bash
ssh root@srv978232  # or your server connection method
cd /var/www/gpr
```

---

## **🗄️ ERROR 1: bundling_id Column Missing**

### **Step 1: Upload Migration File**
Upload file ini ke production server: `database/migrations/2025_09_02_055639_add_bundling_id_to_detail_transactions_table.php`

### **Step 2: Run Migration on Production**
```bash
cd /var/www/gpr

# Check current migration status
php artisan migrate:status

# Run the new migration
php artisan migrate --force

# Verify column was added
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
echo 'bundling_id exists: ' . (Schema::hasColumn('detail_transactions', 'bundling_id') ? 'YES' : 'NO');
"
```

---

## **📧 ERROR 2: Email SMTP Configuration**

### **Option A: Disable Email Verification (Quick Fix)**
```bash
cd /var/www/gpr

# Edit .env file
nano .env

# Add/update these lines:
MAIL_MAILER=log
MAIL_LOG_CHANNEL=single
```

### **Option B: Configure Proper SMTP (Recommended)**
```bash
# Edit .env file
nano .env

# Add proper SMTP configuration:
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com  # or your SMTP server
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@globalphotorental.com
MAIL_FROM_NAME="Global Photo Rental"
```

### **Option C: Use Local Sendmail (Server)**
```bash
# Check if sendmail is available
which sendmail

# If available, configure in .env:
MAIL_MAILER=sendmail
MAIL_SENDMAIL_PATH="/usr/sbin/sendmail -bs -i"
```

---

## **⚡ COMPLETE DEPLOYMENT SCRIPT**

Run this complete script on production server:

```bash
#!/bin/bash

echo "🚀 Fixing GPR Production Errors..."
echo "================================="

# Step 1: Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Step 2: Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Step 3: Update .env for email (temporary fix)
echo "📧 Fixing email configuration..."
grep -q "MAIL_MAILER" .env || echo "MAIL_MAILER=log" >> .env
sed -i 's/MAIL_MAILER=.*/MAIL_MAILER=log/' .env

# Step 4: Optimize for production  
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 5: Test fixes
echo "🧪 Testing fixes..."
echo "bundling_id column: $(php artisan tinker --execute='use Illuminate\Support\Facades\Schema; echo Schema::hasColumn("detail_transactions", "bundling_id") ? "EXISTS" : "MISSING";')"

echo ""
echo "✅ Fixes completed!"
echo "🌐 Test your application at: https://admin.globalphotorental.com"
```

---

## **🔍 VERIFICATION COMMANDS**

After deployment, run these to verify fixes:

```bash
# Test 1: Check bundling_id column
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
echo 'bundling_id column exists: ' . (Schema::hasColumn('detail_transactions', 'bundling_id') ? 'YES' : 'NO') . PHP_EOL;
"

# Test 2: Check email configuration
php artisan config:show mail.default

# Test 3: Check migration status
php artisan migrate:status | tail -3

# Test 4: Test application health
curl -I https://admin.globalphotorental.com
```

---

## **📧 EMAIL CONFIGURATION OPTIONS**

### **For Development/Testing:**
```env
MAIL_MAILER=log
MAIL_LOG_CHANNEL=single
```

### **For Production (Gmail):**
```env  
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-specific-password  
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@globalphotorental.com
MAIL_FROM_NAME="Global Photo Rental"
```

### **For Production (Custom SMTP):**
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.globalphotorental.com
MAIL_PORT=587
MAIL_USERNAME=noreply@globalphotorental.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@globalphotorental.com  
MAIL_FROM_NAME="Global Photo Rental"
```

---

## ⚠️ **IMPORTANT NOTES:**

1. **Migration File**: Make sure file `2025_09_02_055639_add_bundling_id_to_detail_transactions_table.php` exists on production server

2. **Email Security**: If using Gmail, create App-Specific Password:
   - Go to Google Account Settings
   - Security → App passwords  
   - Generate password for "Mail"

3. **Backup First**: Before running migrations:
   ```bash
   mysqldump -u gpruser -p gpr > backup_before_fix.sql
   ```

4. **Test After Deploy**: Always test application functionality after deployment

---

## 🎯 **EXPECTED RESULTS:**

After running these fixes:

✅ **bundling_id Error**: RESOLVED  
✅ **Email Error**: RESOLVED (emails will log instead of send)  
✅ **User Registration**: WORKING  
✅ **Application**: FULLY FUNCTIONAL  

**Run these commands on your production server to fix both errors!** 🚀

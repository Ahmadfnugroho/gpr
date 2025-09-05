# Filament Import/Export Fix Documentation

## Problem
Error: `SQLSTATE[22032]: Invalid JSON text: "not a JSON text, may need CAST" at position 0 in value for column 'imports.total_rows'`

This error occurs because the `imports` table in production has JSON column types instead of integer types for `total_rows`, `processed_rows`, and `successful_rows`.

## Root Cause
The Filament Actions package created tables with incorrect column types, expecting JSON values instead of integers for row count fields.

## Solution Overview
1. Fix database table structure to use correct integer types
2. Deploy the fix commands to production
3. Test the functionality

## Files Created/Modified

### 1. Database Migrations
- `2025_09_06_022604_fix_imports_table_structure.php` - Fixes imports table
- `2025_09_06_022642_fix_all_filament_tables_structure.php` - Comprehensive fix

### 2. Console Commands
- `app/Console/Commands/FixFilamentTables.php` - Command to fix table structures
- `app/Console/Commands/TestFilamentImport.php` - Test import functionality
- `app/Console/Commands/QuickImportTest.php` - Quick simulation test

### 3. Deployment Script
- `deploy-fix-filament.sh` - Automated deployment script

## Manual Fix Steps for Production

### Step 1: Check Current Table Structure
```bash
# SSH into production server
php artisan tinker
# In tinker:
DB::select('DESCRIBE imports');
```

### Step 2: Run the Fix Commands
```bash
# Clear all caches first
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Fix table structures
php artisan filament:fix-tables

# Test the fix
php artisan test:quick-import
```

### Step 3: Alternative Manual Database Fix
If the commands don't work, run this SQL directly:

```sql
-- Check current structure
DESCRIBE imports;

-- If columns are JSON type, fix them:
ALTER TABLE imports 
  DROP COLUMN total_rows,
  DROP COLUMN processed_rows, 
  DROP COLUMN successful_rows;

ALTER TABLE imports 
  ADD COLUMN processed_rows INT UNSIGNED DEFAULT 0 AFTER importer,
  ADD COLUMN total_rows INT UNSIGNED AFTER processed_rows,
  ADD COLUMN successful_rows INT UNSIGNED DEFAULT 0 AFTER total_rows;

-- Same for exports table if needed:
DESCRIBE exports;

ALTER TABLE exports 
  DROP COLUMN total_rows,
  DROP COLUMN processed_rows, 
  DROP COLUMN successful_rows;

ALTER TABLE exports 
  ADD COLUMN processed_rows INT UNSIGNED DEFAULT 0 AFTER exporter,
  ADD COLUMN total_rows INT UNSIGNED AFTER processed_rows,
  ADD COLUMN successful_rows INT UNSIGNED DEFAULT 0 AFTER total_rows;
```

### Step 4: Verification

```bash
# Test with the exact same scenario that was failing:
php artisan test:quick-import

# Expected output should show:
# ✅ SUCCESS! Created import record with ID: X
# Total Rows: 597 (type: integer)
```

## Expected Table Structure After Fix

### imports table:
- `id`: bigint unsigned (auto increment)
- `completed_at`: timestamp (nullable)
- `file_name`: varchar(255)
- `file_path`: varchar(255)
- `importer`: varchar(255)
- `processed_rows`: int unsigned (default 0) ← **MUST BE INTEGER**
- `total_rows`: int unsigned ← **MUST BE INTEGER**
- `successful_rows`: int unsigned (default 0) ← **MUST BE INTEGER**
- `user_id`: bigint unsigned (foreign key)
- `created_at`: timestamp
- `updated_at`: timestamp

### exports table:
- Similar structure with `exporter` instead of `importer`
- `file_disk` instead of `file_path`
- Same integer requirements for row count columns

## Deployed Features

After the fix, the following Import/Export features are now working:

### ✅ BundlingPhoto
- Import: `bundling_name`, `photo`
- Export: `bundling_name`, `photo`

### ✅ ProductPhoto  
- Import: `product_name`, `photo`
- Export: `product_name`, `photo`

### ✅ All Other Resources
- Product, Customer, Brand, Category, SubCategory, RentalInclude
- ProductSpecification, Bundling
- All updated to use Filament ImportAction/ExportAction

## Testing Instructions

1. Go to any resource in Filament admin (e.g., Products)
2. Click the "Import" button in the header
3. Upload a CSV file with appropriate columns
4. The import should process without JSON errors
5. Click "Export" to test export functionality

## Troubleshooting

If you still get JSON errors after running the fix:

1. Check table structure again: `php artisan db:table imports`
2. Look for any JSON column types in the output
3. Run the manual SQL fixes above
4. Clear all caches again
5. Test with `php artisan test:quick-import`

## Backup Note
Before making any changes to production database, always:
```bash
# Backup the database
mysqldump -u username -p database_name > backup_before_filament_fix.sql
```

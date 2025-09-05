# Customer Import/Export Fix Documentation

## Problem Fixed
Error: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'nama_lengkap' in 'field list'`

This error occurred because CustomerImporter was trying to insert Indonesian column names (`nama_lengkap`, `nomor_hp_1`, etc.) directly into the database, but the actual database columns use English names.

## Root Cause
The CustomerImporter was defined with Indonesian column names in `getColumns()`, but Filament automatically tries to insert all defined columns directly into the database. The mapping was only happening in `beforeSave()` which was too late.

## Solution Applied

### ✅ Updated CustomerImporter (`app/Filament/Imports/CustomerImporter.php`)
Changed column definitions from Indonesian to English database field names:

**Before:**
```php
ImportColumn::make('nama_lengkap') // ❌ Doesn't exist in database
ImportColumn::make('nomor_hp_1')   // ❌ Doesn't exist in database
ImportColumn::make('alamat')       // ❌ Doesn't exist in database
```

**After:**
```php
ImportColumn::make('name')             // ✅ Maps to database `name` column
ImportColumn::make('phone_number_1')  // ✅ Handled in afterSave() method
ImportColumn::make('address')          // ✅ Maps to database `address` column
```

### ✅ Updated CustomerExporter (`app/Filament/Exports/CustomerExporter.php`)
Changed export columns for consistency:

**Before:**
```php
ExportColumn::make('nama_lengkap') // Indonesian header
```

**After:**
```php
ExportColumn::make('name')->label('Nama Lengkap') // English column, Indonesian label
```

### ✅ Column Mapping

| Import Column | Database Field | Handled By |
|---------------|----------------|------------|
| `name` | `name` | Direct mapping |
| `email` | `email` | Direct mapping |
| `phone_number_1` | `customerPhoneNumbers` relation | `afterSave()` method |
| `phone_number_2` | `customerPhoneNumbers` relation | `afterSave()` method |
| `gender` | `gender` | `beforeSave()` with conversion |
| `status` | `status` | Direct mapping with default |
| `address` | `address` | Direct mapping |
| `job` | `job` | Direct mapping |
| `office_address` | `office_address` | Direct mapping |
| `instagram_username` | `instagram_username` | Direct mapping |
| `emergency_contact_name` | `emergency_contact_name` | Direct mapping |
| `emergency_contact_number` | `emergency_contact_number` | Direct mapping |
| `source_info` | `source_info` | Direct mapping |

### ✅ Phone Number Handling
Phone numbers are handled separately because they're stored in a related `customer_phone_numbers` table:
- `phone_number_1` → First phone number record
- `phone_number_2` → Second phone number record (if provided)

### ✅ Gender Conversion
The `beforeSave()` method still handles gender conversion:
- `'laki-laki'`, `'l'`, `'male'`, `'pria'` → `'male'`
- `'perempuan'`, `'p'`, `'female'`, `'wanita'` → `'female'`

## Testing
Created `php artisan test:customer-importer` command which verified:
- ✅ All column names match database fields
- ✅ Customer creation works with new column mapping
- ✅ Phone number relations work correctly

## CSV Format Expected Now
When importing customers, use these column headers:

```csv
name,email,phone_number_1,phone_number_2,gender,status,address,job,office_address,instagram_username,emergency_contact_name,emergency_contact_number,source_info
```

## Final Solution: Custom fillRecord Method

The key fix was overriding the `fillRecord()` method in CustomerImporter to exclude phone number columns from mass assignment:

```php
public function fillRecord(): void
{
    // Get data but exclude phone numbers from mass assignment
    $fillableData = collect($this->data)->except(['phone_number_1', 'phone_number_2'])->toArray();
    
    // Fill the record with safe data only
    $this->record->fill($fillableData);
}
```

This prevents Filament from trying to insert `phone_number_1` and `phone_number_2` directly into the `customers` table (where they don't exist), while still allowing the `afterSave()` method to access the phone data and create the proper relationships.

## Testing Results

### ✅ fillRecord Test (`php artisan test:customer-fill`)
- ✅ Original data: 8 fields (including phone numbers)
- ✅ Fillable data: 6 fields (phone numbers excluded)
- ✅ Only valid database columns are filled
- ✅ Phone numbers correctly excluded from mass assignment

### ✅ Phone Handling Test (`php artisan test:customer-phones`)
- ✅ Phone numbers are created in `customer_phone_numbers` table
- ✅ Primary and secondary phone numbers handled correctly
- ✅ Update scenario works (delete old, create new)
- ✅ Phone number accessor returns correct primary phone

## Result
- ✅ Customer import now works without column errors
- ✅ Export generates files with correct data
- ✅ Phone numbers are properly associated with customers via relations
- ✅ All field mappings are consistent with database schema
- ✅ Phone number data remains accessible in afterSave() for relation handling

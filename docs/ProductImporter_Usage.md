# ProductImporter Documentation

## Overview
ProductImporter telah diperbaiki untuk mendukung semua kolom yang diharapkan, termasuk `thumbnail` dan `serial_numbers` yang sebelumnya tidak dihandle.

## Kolom yang Didukung

### 1. **nama_produk** (Required)
- **Alias**: `name`
- **Tipe**: String, max 255 karakter
- **Contoh**: "Canon EOS R5", "Sony A7 III"

### 2. **harga** (Required) 
- **Alias**: `price`
- **Tipe**: Numeric, minimal 0
- **Format**: Mendukung format currency (Rp, $, dll), comma separator
- **Contoh**: "Rp 25,000,000", "25000000", "$2,500"

### 3. **thumbnail** (Optional)
- **Alias**: `foto`
- **Tipe**: String, max 500 karakter
- **Format**: URL atau path ke file gambar
- **Contoh**: "https://example.com/image.jpg", "/storage/products/camera.png"

### 4. **status** (Required)
- **Format**: Mendukung bahasa Indonesia dan English
- **Options**:
  - `available`: tersedia, available, ada, a
  - `unavailable`: tidak tersedia, unavailable, tidak ada, u  
  - `maintenance`: maintenance, perbaikan, service, m
- **Default**: available

### 5. **kategori** (Optional)
- **Alias**: `category`
- **Tipe**: String, max 255 karakter
- **Auto-create**: Kategori baru akan dibuat otomatis jika tidak ada
- **Contoh**: "Kamera", "Lensa", "Aksesoris"

### 6. **brand** (Optional)
- **Tipe**: String, max 255 karakter
- **Auto-create**: Brand baru akan dibuat otomatis jika tidak ada
- **Contoh**: "Canon", "Sony", "Nikon"

### 7. **sub_kategori** (Optional)
- **Alias**: `sub_category`
- **Tipe**: String, max 255 karakter
- **Auto-create**: Sub kategori baru akan dibuat dengan relasi ke parent category
- **Contoh**: "DSLR", "Mirrorless", "Prime Lens"

### 8. **premiere** (Optional)
- **Tipe**: Boolean
- **Format**: ya, yes, true, 1, iya → `true`, lainnya → `false`
- **Default**: false

### 9. **serial_numbers** (Optional)
- **Alias**: `nomor_seri`
- **Tipe**: String dengan multiple values
- **Separator**: Comma (,), Semicolon (;), Pipe (|), Newline
- **Contoh**: 
  - Single: "ABC123456"
  - Multiple: "ABC123456,DEF789012,GHI345678"
  - Multiple lines: "ABC123456\nDEF789012\nGHI345678"

## Contoh Template Excel

| nama_produk | harga | thumbnail | status | kategori | brand | sub_kategori | premiere | serial_numbers |
|------------|--------|-----------|--------|----------|-------|--------------|----------|----------------|
| Canon EOS R5 | 25000000 | /images/canon-r5.jpg | available | Kamera | Canon | Mirrorless | ya | R5001,R5002,R5003 |
| Sony A7 III | 18000000 | /images/sony-a7iii.jpg | tersedia | Kamera | Sony | Mirrorless | tidak | A7III001;A7III002 |

## Fitur Baru

### 1. **Thumbnail Handling**
- Mendukung URL atau path file
- Validasi maksimal 500 karakter
- Optional field - tidak wajib diisi

### 2. **Serial Numbers Processing**
- Otomatis membuat ProductItem untuk setiap serial number
- Validasi duplikasi - serial number harus unique
- Support multiple separator
- Error handling per serial number

### 3. **Enhanced Validation**
- Validasi lengkap untuk semua field
- Error message dalam bahasa Indonesia
- Detailed logging untuk debugging

### 4. **Relasi Management** 
- Auto-create Category, Brand, SubCategory jika belum ada
- SubCategory otomatis linked ke parent Category
- Foreign key constraint handling

## Error Handling

### Common Errors:
1. **Duplicate Product**: "Produk 'XXX' sudah ada"
2. **Duplicate Serial**: "Serial number 'XXX' sudah ada pada produk 'YYY'" 
3. **Invalid Status**: "Status harus: available, unavailable, atau maintenance"
4. **Missing Required**: "Nama produk wajib diisi", "Harga wajib diisi"

## Usage Example

```php
// Import with update existing products
$importer = new ProductImporter(true);
Excel::import($importer, 'products.xlsx');

// Get import results
$results = $importer->getImportResults();
echo "Total: {$results['total']}";
echo "Success: {$results['success']}";
echo "Failed: {$results['failed']}"; 
echo "Updated: {$results['updated']}";

// Show errors
foreach ($results['errors'] as $error) {
    echo $error . "\n";
}
```

## Performance Notes

- **Batch Size**: 100 records per batch
- **Chunk Size**: 100 records per chunk
- **Memory Optimization**: Using chunking for large files
- **Logging**: Comprehensive logging for monitoring

## Breaking Changes

1. **getExpectedHeaders()** updated to include `thumbnail` and `serial_numbers`
2. **SubCategory model** now includes `category_id` in fillable array
3. **Validation rules** expanded for new fields
4. **Serial numbers** now create ProductItem records automatically

## Migration Requirements

If updating from old version, ensure:
1. ProductItem table exists and has proper relations
2. SubCategory model can accept `category_id` 
3. Product model has `thumbnail` field in fillable array

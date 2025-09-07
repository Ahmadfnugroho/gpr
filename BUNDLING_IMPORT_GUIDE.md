# Bundling Import Guide

## ✅ **Perbaikan BundlingImporter**

### **Masalah yang Diperbaiki:**

1. **🔧 Error Handling**: Tidak ada error message yang jelas saat import gagal
2. **🔧 Database Transactions**: Data tidak konsisten jika terjadi error di tengah proses
3. **🔧 Case Sensitivity**: Nama produk harus exact match (case-sensitive)
4. **🔧 Silent Failures**: Product tidak ditemukan tapi tidak ada warning
5. **🔧 Logging**: Tidak ada log untuk debugging

### **Peningkatan yang Dilakukan:**

✅ **Comprehensive Error Handling**: Exception handling dengan pesan error yang jelas
✅ **Database Transactions**: Rollback otomatis jika ada error
✅ **Case-Insensitive Matching**: "canon eos r5" = "Canon EOS R5"
✅ **Detailed Logging**: Log ke Laravel log file untuk debugging
✅ **Better Validation**: Validasi yang lebih ketat untuk semua field
✅ **Duplicate Handling**: Update quantity jika produk sudah ada dalam bundling

## 📋 **Format Import CSV**

### **Kolom yang Diperlukan:**
```csv
name,price,product_name,quantity
```

### **Contoh Data:**
```csv
Basic Package,500000,Test Product 1,1
Basic Package,500000,Test Product 2,1
Premium Package,800000,Test Product 1,2
Premium Package,800000,Test Product 2,1
Premium Package,800000,Test Product 3,1
```

### **Penjelasan:**
- **name**: Nama bundling (akan dibuat baru jika belum ada)
- **price**: Harga bundling dalam Rupiah (tanpa titik/koma)
- **product_name**: Nama produk yang sudah ada di database
- **quantity**: Jumlah produk dalam bundling (minimal 1)

## 🔍 **Cara Import:**

### **1. Persiapan Data**
- Pastikan produk sudah ada di database
- Gunakan nama produk yang exact match (case-insensitive)
- Format harga dalam angka tanpa separator

### **2. Upload File**
- File format: CSV atau Excel
- Gunakan kolom sesuai format di atas
- Satu bundling bisa memiliki multiple baris (satu per produk)

### **3. Debugging**
Jika import gagal, cek:
- **Laravel Log**: `storage/logs/laravel.log`
- **Error Messages**: Akan tampil di Filament notification
- **Product Names**: Pastikan produk ada di database

## 📝 **Tips & Best Practices:**

### **✅ Do's:**
- Import produk terlebih dahulu sebelum bundling
- Gunakan nama produk yang konsisten
- Test dengan data kecil dulu
- Check log file untuk debugging

### **❌ Don'ts:**
- Jangan gunakan harga dengan format currency (Rp 1.000.000)
- Jangan gunakan quantity 0 atau negatif  
- Jangan import bundling jika produk belum ada

## 🚨 **Troubleshooting:**

### **Import Gagal Tanpa Pesan Error:**
1. Check Laravel log: `storage/logs/laravel.log`
2. Pastikan semua produk ada di database
3. Periksa format CSV (encoding UTF-8)
4. Test dengan 1-2 baris data terlebih dahulu

### **Product Not Found:**
```
Product 'Canon EOS R5' not found. Available products: Test Product 1, Test Product 2, ...
```
**Solusi**: Pastikan nama produk di CSV sama dengan yang ada di database

### **Bundling Sudah Ada:**
Bundling akan di-update, produk akan ditambahkan atau quantity di-update

## 📁 **File Sample:**

File `sample_bundlings_import.csv` sudah disediakan dengan format yang benar dan menggunakan produk yang ada di database.

## 🔧 **Technical Changes:**

```php
// Before: Case-sensitive, no error handling
$product = Product::where('name', $productName)->first();

// After: Case-insensitive with error handling  
$product = Product::whereRaw('LOWER(name) = LOWER(?)', [$productName])->first();
if (!$product) {
    throw new Exception("Product '{$productName}' not found. Please check product name.");
}
```

Import bundling sekarang akan memberikan error message yang jelas dan log yang detail untuk debugging! 🚀

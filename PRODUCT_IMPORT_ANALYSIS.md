# 📊 ANALISIS STRUKTUR ProductImporter

## 🔍 **STRUKTUR DATA YANG DIHARAPKAN**

### ✅ **KOLOM YANG WAJIB ADA** (Required)
1. **`nama_produk`** atau **`name`** - Nama produk (string, max 255)
2. **`harga`** atau **`price`** - Harga produk (numeric, min 0)
3. **`status`** - Status produk (required, enum)

### ✅ **KOLOM OPSIONAL** (Optional)
4. **`kategori`** atau **`category`** - Nama kategori (string)
5. **`brand`** - Nama brand (string)
6. **`sub_kategori`** atau **`sub_category`** - Nama sub kategori (string)
7. **`premiere`** - Produk premium (boolean: ya/tidak)

### ❌ **KOLOM YANG TIDAK ADA DI IMPORTER**
- **`thumbnail`** - Tidak dihandle oleh ProductImporter
- **`serial_numbers`** - Tidak dihandle oleh ProductImporter (ada di model terpisah ProductItem)

---

## 📋 **FORMAT EXCEL TEMPLATE**

### Header Row (Baris 1):
```
nama_produk | harga | status | kategori | brand | sub_kategori | premiere
```

### Sample Data (Baris 2):
```
Camera DSLR Canon EOS R5 | 45000000 | available | Kamera | Canon | DSLR | ya
```

---

## 🎯 **DETAIL FIELD SPECIFICATIONS**

### 1. **Nama Produk** (`nama_produk`)
- **Type**: String
- **Required**: Ya
- **Max Length**: 255 karakter
- **Auto-generates**: `slug` dari nama produk

### 2. **Harga** (`harga`)
- **Type**: Numeric
- **Required**: Ya
- **Min Value**: 0
- **Format**: Bisa dengan/tanpa format currency (Rp, $, koma, titik)
- **Example**: `45000000`, `Rp 45.000.000`, `$45,000`

### 3. **Status** (`status`)
- **Type**: Enum
- **Required**: Ya
- **Valid Values**: 
  - `available` / `tersedia` / `ada` / `a`
  - `unavailable` / `tidak tersedia` / `tidak ada` / `u`  
  - `maintenance` / `perbaikan` / `service` / `m`
- **Default**: `available`

### 4. **Kategori** (`kategori`)
- **Type**: String (Optional)
- **Behavior**: Auto-create jika tidak ada
- **Relationship**: `Product.category_id -> Category.id`
- **Auto-generates**: `slug` dari nama kategori

### 5. **Brand** (`brand`)
- **Type**: String (Optional)  
- **Behavior**: Auto-create jika tidak ada
- **Relationship**: `Product.brand_id -> Brand.id`
- **Auto-generates**: `slug` dari nama brand

### 6. **Sub Kategori** (`sub_kategori`)
- **Type**: String (Optional)
- **Behavior**: Auto-create jika tidak ada
- **Relationship**: `Product.sub_category_id -> SubCategory.id`
- **Dependencies**: Akan dikaitkan dengan kategori yang sama
- **Auto-generates**: `slug` dari nama sub kategori

### 7. **Premiere** (`premiere`)
- **Type**: Boolean (Optional)
- **Valid Values**: 
  - `ya` / `yes` / `true` / `1` / `iya` = true
  - Kosong atau nilai lain = false
- **Default**: false

---

## 🚫 **KOLOM YANG TIDAK DIHANDLE**

### ❌ **Thumbnail**
- **Alasan**: File upload tidak bisa dihandle via Excel import
- **Solusi**: Upload manual setelah import atau via API terpisah
- **Alternative**: Bisa ditambahkan URL path di importer jika dibutuhkan

### ❌ **Serial Numbers** 
- **Alasan**: Serial numbers ada di model `ProductItem` yang terpisah
- **Relationship**: `Product` -> `ProductItem` (HasMany)
- **Solusi**: Buat importer terpisah untuk ProductItem atau extend ProductImporter

---

## 📂 **RELASI DATABASE**

### Product Table:
```sql
products:
- id (primary key)
- name (string)
- price (decimal)
- thumbnail (string, nullable)
- status (enum)
- slug (string)
- category_id (foreign key, nullable)
- brand_id (foreign key, nullable) 
- sub_category_id (foreign key, nullable)
- premiere (boolean)
```

### Related Tables:
```sql
categories:
- id (primary key)
- name (string)
- slug (string)

brands:
- id (primary key)  
- name (string)
- slug (string)

sub_categories:
- id (primary key)
- name (string)
- slug (string)
- category_id (foreign key)

product_items:
- id (primary key)
- product_id (foreign key)
- serial_number (string)
- is_available (boolean)
```

---

## 📝 **TEMPLATE EXCEL YANG BENAR**

### File: `template_product_import.xlsx`

| nama_produk | harga | status | kategori | brand | sub_kategori | premiere |
|-------------|-------|--------|----------|-------|--------------|----------|
| Camera DSLR Canon EOS R5 | 45000000 | available | Kamera | Canon | DSLR | ya |
| Lensa Sony 85mm f/1.4 | 25000000 | available | Lensa | Sony | Prime | tidak |
| Tripod Manfrotto Professional | 3500000 | maintenance | Aksesoris | Manfrotto | Tripod | ya |

### Alternative Template (English Headers):

| name | price | status | category | brand | sub_category | premiere |
|------|-------|--------|----------|-------|--------------|----------|
| Camera DSLR Canon EOS R5 | 45000000 | available | Camera | Canon | DSLR | yes |

---

## ⚙️ **BEHAVIOR NOTES**

### Auto-Creation:
- **Category**: Jika nama kategori belum ada, akan otomatis dibuat
- **Brand**: Jika nama brand belum ada, akan otomatis dibuat  
- **SubCategory**: Jika sub kategori belum ada, akan dibuat dan dikaitkan dengan category

### Data Normalization:
- **Price**: Currency symbols dan format akan dihapus otomatis
- **Status**: Case-insensitive, akan dinormalisasi ke enum values
- **Boolean**: Text seperti "ya", "yes", "true" akan dikonversi ke boolean

### Error Handling:
- **Duplicate Products**: Berdasarkan `name`, bisa di-update jika `updateExisting = true`
- **Invalid Data**: Will be logged dengan row number dan error message
- **Missing Required**: Akan gagal validasi dan dicatat di error log

---

## 🚀 **IMPLEMENTASI UNTUK SERIAL NUMBERS**

Jika Anda perlu handle serial numbers, ada dua opsi:

### Option 1: Extend ProductImporter
```php
// Tambah kolom di Excel: serial_numbers (comma-separated)
// Example: "SN001,SN002,SN003"

// Dalam ProductImporter, tambahkan:
protected function createProduct(array $data, int $rowNumber): void
{
    // Create product
    $product = Product::create([...]);
    
    // Create serial numbers if provided
    if (!empty($data['serial_numbers'])) {
        $serialNumbers = explode(',', $data['serial_numbers']);
        foreach ($serialNumbers as $serialNumber) {
            ProductItem::create([
                'product_id' => $product->id,
                'serial_number' => trim($serialNumber),
                'is_available' => true
            ]);
        }
    }
}
```

### Option 2: Separate ProductItemImporter
Buat importer terpisah untuk handle ProductItem dengan kolom:
- `product_name` (reference ke Product)
- `serial_number`
- `is_available`

---

## ✅ **CHECKLIST IMPLEMENTASI**

- ✅ `nama_produk` (required)
- ✅ `harga` (required)  
- ✅ `status` (required)
- ✅ `kategori` (optional, auto-create)
- ✅ `brand` (optional, auto-create)
- ✅ `sub_kategori` (optional, auto-create)
- ✅ `premiere` (optional, boolean)
- ❌ `thumbnail` (not implemented)
- ❌ `serial_numbers` (not implemented)

**Untuk implement thumbnail & serial numbers, perlu modifikasi ProductImporter atau buat importer terpisah.**

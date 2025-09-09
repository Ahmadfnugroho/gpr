# Test All Endpoints with Available Quantity

## 📋 Summary of Updated Endpoints

Semua endpoint berikut sekarang mendukung parameter `start_date` dan `end_date` untuk menghitung `available_quantity`:

### ✅ Product Endpoints
```bash
# Products collection
GET /api/products?start_date=2025-12-25&end_date=2025-12-27

# Single product
GET /api/product/sony-a7c?start_date=2025-12-25&end_date=2025-12-27
```

### ✅ Bundling Endpoints
```bash
# Bundlings collection
GET /api/bundlings?start_date=2025-12-25&end_date=2025-12-27

# Single bundling (PENTING: gunakan /api/bundling/ bukan /api/bundlings/)
GET /api/bundling/sony-a7c-sony-fe-50mm-f18c?start_date=2025-12-25&end_date=2025-12-27
```

### ✅ Brand Endpoints
```bash
# Single brand dengan products
GET /api/brand/sony?start_date=2025-12-25&end_date=2025-12-27
```

### ✅ Category Endpoints
```bash
# Single category dengan products
GET /api/category/camera?start_date=2025-12-25&end_date=2025-12-27
```

---

## 🧪 Test Cases

### 1. Test URL yang Anda coba sebelumnya (FIXED)

**❌ URL yang salah:**
```
http://gpr.id/api/bundlings/sony-a7c-sony-fe-50mm-f18c?start_date=2025-12-25&end_date=2025-12-27
```

**✅ URL yang benar:**
```
http://gpr.id/api/bundling/sony-a7c-sony-fe-50mm-f18c?start_date=2025-12-25&end_date=2025-12-27
```

### 2. Test Products
```bash
# Tanpa tanggal
curl "http://gpr.id/api/products?limit=3"

# Dengan tanggal
curl "http://gpr.id/api/products?start_date=2025-12-25&end_date=2025-12-27&limit=3"

# Single product tanpa tanggal
curl "http://gpr.id/api/product/sony-a7c"

# Single product dengan tanggal
curl "http://gpr.id/api/product/sony-a7c?start_date=2025-12-25&end_date=2025-12-27"
```

**Expected Response:**
```json
{
  "data": {
    "id": 181,
    "name": "Sony A7C",
    "quantity": 2,
    "available_quantity": 2, // <- Field baru
    "price": 300000,
    // ... other fields
  }
}
```

### 3. Test Bundlings
```bash
# Collection bundlings tanpa tanggal
curl "http://gpr.id/api/bundlings?limit=3"

# Collection bundlings dengan tanggal
curl "http://gpr.id/api/bundlings?start_date=2025-12-25&end_date=2025-12-27&limit=3"

# Single bundling tanpa tanggal
curl "http://gpr.id/api/bundling/sony-a7c-sony-fe-50mm-f18c"

# Single bundling dengan tanggal
curl "http://gpr.id/api/bundling/sony-a7c-sony-fe-50mm-f18c?start_date=2025-12-25&end_date=2025-12-27"
```

**Expected Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Sony A7C + Sony FE 50mm f/1.8c",
    "price": 450000,
    "products": [
      {
        "id": 181,
        "name": "Sony A7C",
        "quantity": 1,
        "available_quantity": 2, // <- Field baru untuk setiap produk dalam bundling
        // ... other fields
      },
      {
        "id": 182,
        "name": "Sony FE 50mm f/1.8c",
        "quantity": 1,
        "available_quantity": 3, // <- Field baru untuk setiap produk dalam bundling
        // ... other fields
      }
    ]
  }
}
```

### 4. Test Brands
```bash
# Single brand tanpa tanggal
curl "http://gpr.id/api/brand/sony"

# Single brand dengan tanggal
curl "http://gpr.id/api/brand/sony?start_date=2025-12-25&end_date=2025-12-27"
```

**Expected Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Sony",
    "slug": "sony",
    "products": [
      {
        "id": 181,
        "name": "Sony A7C",
        "quantity": 2,
        "available_quantity": 2, // <- Field baru
        // ... other fields
      }
    ]
  }
}
```

### 5. Test Categories
```bash
# Single category tanpa tanggal
curl "http://gpr.id/api/category/camera"

# Single category dengan tanggal
curl "http://gpr.id/api/category/camera?start_date=2025-12-25&end_date=2025-12-27"
```

---

## 🔧 Frontend Integration Examples

### JavaScript Fetch dengan Date Range
```javascript
// Fetch products dengan date range
const fetchProductsWithDates = async (startDate, endDate) => {
  const url = new URL('http://gpr.id/api/products');
  if (startDate && endDate) {
    url.searchParams.append('start_date', startDate);
    url.searchParams.append('end_date', endDate);
  }
  
  const response = await fetch(url);
  const data = await response.json();
  return data.data; // Array of products with available_quantity
};

// Fetch bundlings dengan date range
const fetchBundlingsWithDates = async (startDate, endDate) => {
  const url = new URL('http://gpr.id/api/bundlings');
  if (startDate && endDate) {
    url.searchParams.append('start_date', startDate);
    url.searchParams.append('end_date', endDate);
  }
  
  const response = await fetch(url);
  const data = await response.json();
  return data.data; // Array of bundlings with available_quantity for each product
};

// Fetch single bundling dengan date range
const fetchBundlingWithDates = async (slug, startDate, endDate) => {
  const url = new URL(`http://gpr.id/api/bundling/${slug}`);
  if (startDate && endDate) {
    url.searchParams.append('start_date', startDate);
    url.searchParams.append('end_date', endDate);
  }
  
  const response = await fetch(url);
  const data = await response.json();
  return data.data; // Single bundling with available_quantity for each product
};

// Usage with rental duration helper
import { getRentalDays } from './rental-duration-helper';

const checkAvailability = async () => {
  const startDate = '2025-12-25';
  const endDate = '2025-12-27';
  const duration = getRentalDays(startDate, endDate); // 3 days
  
  const products = await fetchProductsWithDates(startDate, endDate);
  const availableProducts = products.filter(p => p.available_quantity > 0);
  
  console.log(`Found ${availableProducts.length} available products for ${duration} days`);
};
```

### React Component Example
```jsx
import React, { useState, useEffect } from 'react';
import { getRentalDays } from './rental-duration-helper';

const ProductAvailability = () => {
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [products, setProducts] = useState([]);
  const [bundlings, setBundlings] = useState([]);

  useEffect(() => {
    if (startDate && endDate) {
      Promise.all([
        fetchProductsWithDates(startDate, endDate),
        fetchBundlingsWithDates(startDate, endDate)
      ]).then(([productsData, bundlingsData]) => {
        setProducts(productsData);
        setBundlings(bundlingsData);
      });
    }
  }, [startDate, endDate]);

  const duration = getRentalDays(startDate, endDate);

  return (
    <div>
      <div>
        <input 
          type="date" 
          value={startDate}
          onChange={(e) => setStartDate(e.target.value)}
        />
        <input 
          type="date" 
          value={endDate}
          onChange={(e) => setEndDate(e.target.value)}
        />
      </div>
      
      {duration > 0 && (
        <p>Duration: {duration} days</p>
      )}

      <div>
        <h3>Available Products</h3>
        {products.map(product => (
          <div key={product.id}>
            <h4>{product.name}</h4>
            <p>Available: {product.available_quantity}/{product.quantity}</p>
          </div>
        ))}
      </div>

      <div>
        <h3>Available Bundlings</h3>
        {bundlings.map(bundling => (
          <div key={bundling.id}>
            <h4>{bundling.name}</h4>
            {bundling.products.map(product => (
              <div key={product.id}>
                <span>{product.name}: {product.available_quantity} available</span>
              </div>
            ))}
          </div>
        ))}
      </div>
    </div>
  );
};
```

---

## ⚠️ Important Notes

1. **Route Differences:**
   - Products: `/api/product/{slug}` (singular)
   - Bundlings: `/api/bundling/{slug}` (singular) ← PENTING!
   - Collections: `/api/products`, `/api/bundlings` (plural)

2. **Date Format:**
   - Must be `Y-m-d` format (e.g., `2025-12-25`)
   - `end_date` must be >= `start_date`

3. **Behavior:**
   - Without dates: `available_quantity = total items count`
   - With dates: `available_quantity = available items for that period`

4. **Performance:**
   - Menggunakan eager loading dan withCount untuk menghindari N+1 queries
   - Caching sudah ada di model level

---

## 🚀 Next Steps

1. Test URL yang benar: `/api/bundling/` bukan `/api/bundlings/`
2. Verify semua endpoint mengembalikan `available_quantity`
3. Test dengan data booking yang existing untuk melihat perbedaan quantity
4. Integrate dengan React frontend menggunakan rental duration helper
5. Add error handling untuk invalid dates atau missing bundlings

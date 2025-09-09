# Test API Available Quantity

## Test Endpoints yang sudah diupdate:

### 1. GET /api/products (Collection with date range)
```bash
# Test tanpa parameter tanggal (default: total items)
curl "http://gpr.id/api/products"

# Test dengan parameter tanggal
curl "http://gpr.id/api/products?start_date=2025-12-25&end_date=2025-12-27"
```

**Expected Response:**
```json
{
  "data": [
    {
      "id": 181,
      "name": "Sony A7C",
      "quantity": 2,
      "available_quantity": 2,  // <- Field ini yang baru ditambahkan
      "price": 300000,
      // ... other fields
    }
  ]
}
```

### 2. GET /api/product/{slug} (Single product with date range)
```bash
# Test tanpa parameter tanggal
curl "http://gpr.id/api/product/sony-a7c"

# Test dengan parameter tanggal
curl "http://gpr.id/api/product/sony-a7c?start_date=2025-12-25&end_date=2025-12-27"
```

**Expected Response:**
```json
{
  "data": {
    "id": 181,
    "name": "Sony A7C",
    "quantity": 2,
    "available_quantity": 2,  // <- Field ini yang baru ditambahkan
    "price": 300000,
    "thumbnail": null,
    "status": "available",
    "is_available": true,
    "description": "",
    "slug": "sony-a7c",
    "premiere": false,
    // ... other fields
  }
}
```

## Validation Tests:

### Test Validation Error
```bash
# Test dengan invalid date format
curl "http://gpr.id/api/product/sony-a7c?start_date=invalid&end_date=2025-12-27"

# Test dengan end_date lebih kecil dari start_date
curl "http://gpr.id/api/product/sony-a7c?start_date=2025-12-27&end_date=2025-12-25"
```

**Expected Response:**
```json
{
  "message": "The start date field must be a valid date with format Y-m-d.",
  "errors": {
    "start_date": ["The start date field must be a valid date with format Y-m-d."]
  }
}
```

## Edge Cases Tests:

### Test Partial Parameters
```bash
# Test dengan hanya start_date (should ignore and return total items)
curl "http://gpr.id/api/product/sony-a7c?start_date=2025-12-25"

# Test dengan hanya end_date (should ignore and return total items)
curl "http://gpr.id/api/product/sony-a7c?end_date=2025-12-27"
```

### Test Date Overlapping Logic
```bash
# Test untuk produk yang sedang disewa dalam periode tersebut
curl "http://gpr.id/api/product/sony-a7c?start_date=2025-01-01&end_date=2025-01-03"
```

## Frontend Integration Test:

### JavaScript Fetch Example
```javascript
const fetchProductWithAvailability = async (productSlug, startDate, endDate) => {
  try {
    const url = new URL(`http://gpr.id/api/product/${productSlug}`);
    
    if (startDate && endDate) {
      url.searchParams.append('start_date', startDate);
      url.searchParams.append('end_date', endDate);
    }
    
    const response = await fetch(url);
    const data = await response.json();
    
    console.log('Available quantity:', data.data.available_quantity);
    return data;
  } catch (error) {
    console.error('Error fetching product:', error);
  }
};

// Test usage
fetchProductWithAvailability('sony-a7c', '2025-12-25', '2025-12-27');
```

## Performance Test:

```bash
# Test dengan banyak produk
curl "http://gpr.id/api/products?start_date=2025-12-25&end_date=2025-12-27&limit=50"

# Measure response time
curl -w "@curl-format.txt" "http://gpr.id/api/product/sony-a7c?start_date=2025-12-25&end_date=2025-12-27"
```

## curl-format.txt (untuk performance test):
```
     time_namelookup:  %{time_namelookup}s\n
        time_connect:  %{time_connect}s\n
     time_appconnect:  %{time_appconnect}s\n
    time_pretransfer:  %{time_pretransfer}s\n
       time_redirect:  %{time_redirect}s\n
  time_starttransfer:  %{time_starttransfer}s\n
                     ----------\n
          time_total:  %{time_total}s\n
```

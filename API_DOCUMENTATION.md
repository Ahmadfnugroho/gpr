# 🔌 **API DOCUMENTATION - GLOBAL PHOTO RENTAL**

## 📋 **Overview**
API Global Photo Rental menyediakan akses programatis ke data produk, kategori, brand, dan transaksi rental equipment fotografer.

**Base URL:** `https://admin.globalphotorental.com/api`

---

## 🔐 **AUTHENTICATION**

### **Public Endpoints** 
Endpoints berikut dapat diakses tanpa authentication:
- `GET /categories` - List kategori
- `GET /brands` - List brand  
- `GET /brands-premiere` - List brand premiere
- `GET /products` - List produk
- `GET /BrowseProduct` - Browse produk homepage
- `GET /bundlings` - List bundling
- `GET /sub-categories` - List sub-kategori

### **Protected Endpoints**
Endpoints berikut memerlukan API key:
- Semua operasi `POST`, `PUT`, `DELETE`
- Operasi transaksi dan sync

### **API Key Usage**
Tambahkan header berikut pada request:
```http
X-API-KEY: your_api_key_here
```

**Error Responses:**
```json
// Missing API key
{
    "message": "API key required",
    "error": "Missing X-API-KEY header",
    "hint": "Add X-API-KEY header with a valid API key"
}

// Invalid API key  
{
    "message": "Invalid API key",
    "error": "API key not found"
}

// Expired API key
{
    "message": "API key expired", 
    "error": "This API key expired on 2024-12-31"
}
```

---

## 📱 **ENDPOINTS**

### **🏷️ CATEGORIES**

#### **GET /api/categories**
List semua kategori
```http
GET /api/categories
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Camera",
            "slug": "camera",
            "created_at": "2024-01-01T10:00:00.000000Z"
        }
    ]
}
```

#### **GET /api/category/{slug}**
Detail kategori berdasarkan slug
```http
GET /api/category/camera
```

#### **POST /api/categories** 🔒
Buat kategori baru (memerlukan API key)
```http
POST /api/categories
Content-Type: application/json
X-API-KEY: your_api_key

{
    "name": "Lighting",
    "description": "Peralatan pencahayaan"
}
```

---

### **🏢 BRANDS**

#### **GET /api/brands**
List semua brand
```http
GET /api/brands
```

#### **GET /api/brands-premiere**  
List brand premiere saja
```http
GET /api/brands-premiere
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Sony",
            "slug": "sony", 
            "premiere": true,
            "logo": "/storage/brands/sony-logo.png"
        }
    ]
}
```

#### **GET /api/brand/{slug}**
Detail brand berdasarkan slug
```http
GET /api/brand/sony
```

---

### **📷 PRODUCTS**

#### **GET /api/products**
List produk dengan pagination
```http
GET /api/products?page=1&per_page=20
```

**Query Parameters:**
- `page` - Nomor halaman (default: 1)
- `per_page` - Jumlah per halaman (default: 20, max: 100)
- `category` - Filter berdasarkan kategori slug
- `brand` - Filter berdasarkan brand slug
- `search` - Search berdasarkan nama produk

**Example:**
```http
GET /api/products?category=camera&brand=sony&search=a7&per_page=10
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Sony A7 III",
            "slug": "sony-a7-iii",
            "price": 500000,
            "thumbnail": "/storage/products/sony-a7-iii.jpg",
            "category": {
                "name": "Camera",
                "slug": "camera"
            },
            "brand": {
                "name": "Sony", 
                "slug": "sony"
            },
            "available_items": 3,
            "total_items": 5
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 150,
        "last_page": 8
    }
}
```

#### **GET /api/BrowseProduct**
Produk untuk homepage/browse (featured products)
```http
GET /api/BrowseProduct
```

#### **GET /api/product/{slug}**
Detail produk berdasarkan slug
```http
GET /api/product/sony-a7-iii
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Sony A7 III",
        "slug": "sony-a7-iii",
        "description": "Full frame mirrorless camera",
        "price": 500000,
        "thumbnail": "/storage/products/sony-a7-iii.jpg",
        "gallery": [
            "/storage/products/sony-a7-iii-1.jpg",
            "/storage/products/sony-a7-iii-2.jpg"
        ],
        "specifications": [
            {
                "name": "Sensor",
                "value": "Full Frame CMOS"
            },
            {
                "name": "Resolution", 
                "value": "24.2 MP"
            }
        ],
        "rental_includes": [
            {
                "name": "Battery Grip",
                "quantity": 1
            }
        ],
        "available_items": 3,
        "total_items": 5,
        "serial_numbers": ["SN001", "SN002", "SN003"]
    }
}
```

---

### **📦 BUNDLINGS**

#### **GET /api/bundlings**
List bundling packages
```http
GET /api/bundlings
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Wedding Photography Package",
            "slug": "wedding-photography-package",
            "price": 1500000,
            "products": [
                {
                    "name": "Sony A7 III",
                    "quantity": 2
                },
                {
                    "name": "Sony 24-70mm", 
                    "quantity": 1
                }
            ]
        }
    ]
}
```

#### **GET /api/bundling/{slug}**
Detail bundling berdasarkan slug
```http
GET /api/bundling/wedding-photography-package
```

---

### **🔍 SEARCH**

#### **GET /api/search-suggestions**
Autocomplete suggestions untuk search
```http
GET /api/search-suggestions?q=sony
```

**Response:**
```json
{
    "suggestions": [
        {
            "type": "product",
            "name": "Sony A7 III",
            "slug": "sony-a7-iii"
        },
        {
            "type": "brand", 
            "name": "Sony",
            "slug": "sony"
        }
    ]
}
```

---

### **💳 TRANSACTIONS** 🔒

#### **POST /api/transaction**
Buat transaksi rental baru (memerlukan API key)
```http
POST /api/transaction
Content-Type: application/json
X-API-KEY: your_api_key

{
    "customer_id": 1,
    "start_date": "2024-12-01",
    "end_date": "2024-12-03", 
    "items": [
        {
            "type": "product",
            "id": 1,
            "quantity": 1
        },
        {
            "type": "bundling",
            "id": 1,
            "quantity": 1
        }
    ],
    "promo_code": "WEDDING20",
    "notes": "Wedding di Jakarta"
}
```

**Response:**
```json
{
    "data": {
        "id": "GPR12345",
        "booking_id": "GPR12345",
        "status": "booking",
        "customer": {
            "name": "Ahmad Fauzi",
            "email": "ahmad@example.com"
        },
        "rental_period": {
            "start_date": "2024-12-01",
            "end_date": "2024-12-03",
            "duration_days": 2
        },
        "items": [
            {
                "name": "Sony A7 III",
                "type": "product", 
                "quantity": 1,
                "price": 500000,
                "subtotal": 1000000
            }
        ],
        "pricing": {
            "subtotal": 1000000,
            "discount": 200000,
            "grand_total": 800000
        }
    }
}
```

#### **POST /api/check-transaction**
Cek status transaksi
```http
POST /api/check-transaction
Content-Type: application/json
X-API-KEY: your_api_key

{
    "booking_id": "GPR12345"
}
```

---

## 📊 **RATE LIMITING**

- **Public endpoints**: 120 requests per minute
- **Protected endpoints**: 60 requests per minute  
- **Search suggestions**: 60 requests per minute

**Rate limit headers:**
```http
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 119
X-RateLimit-Reset: 1640995200
```

---

## 🚨 **ERROR HANDLING**

### **HTTP Status Codes**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized  
- `404` - Not Found
- `422` - Validation Error
- `429` - Rate Limit Exceeded
- `500` - Internal Server Error

### **Error Response Format**
```json
{
    "message": "Validation failed",
    "errors": {
        "name": [
            "The name field is required."
        ],
        "email": [
            "The email field must be a valid email address."
        ]
    }
}
```

---

## 🔧 **DEVELOPMENT & TESTING**

### **Local Setup**
1. Jalankan server development:
   ```bash
   php artisan serve
   ```

2. Seed API keys:
   ```bash
   php artisan db:seed --class=ApiKeySeeder
   ```

3. List API keys:
   ```bash
   php artisan api:key list
   ```

### **Testing Endpoints**
```bash
# Test public endpoints
curl -X GET "http://localhost:8000/api/categories" \
  -H "Accept: application/json"

# Test protected endpoints
curl -X POST "http://localhost:8000/api/categories" \
  -H "Accept: application/json" \
  -H "X-API-KEY: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Category"}'
```

### **API Key Management**
```bash
# Create new API key
php artisan api:key create --name="Frontend App"

# List all keys
php artisan api:key list

# Deactivate key
php artisan api:key deactivate --key="your_key_here"
```

---

## 📈 **MONITORING & LOGS**

### **API Usage Logs**
Setiap penggunaan API key dicatat dengan informasi:
- Nama API key
- Endpoint yang diakses
- Method HTTP
- IP address
- Timestamp

### **Log Location**
```bash
tail -f storage/logs/laravel.log | grep "API key used"
```

### **Performance Monitoring**
- Response time tracking
- Memory usage monitoring
- Rate limit monitoring
- Error rate tracking

---

## 🔒 **SECURITY BEST PRACTICES**

1. **API Key Management:**
   - Rotate keys secara berkala
   - Set expiration date yang sesuai
   - Monitor penggunaan key

2. **HTTPS Only:**
   - Selalu gunakan HTTPS di production
   - Jangan kirim API key via URL parameters

3. **Rate Limiting:**
   - Implementasi rate limiting per IP
   - Monitor untuk abuse patterns

4. **Input Validation:**
   - Validasi semua input
   - Sanitasi data sebelum processing

5. **Error Handling:**
   - Jangan expose sensitive information
   - Log semua errors untuk monitoring

---

## 📞 **SUPPORT**

**Technical Support:**
- Email: tech@globalphotorental.com
- Documentation: [API Docs](https://admin.globalphotorental.com/docs)
- Status Page: [API Status](https://status.globalphotorental.com)

**API Version:** v1.0
**Last Updated:** December 2024

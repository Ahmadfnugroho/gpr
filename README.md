# 📸 **Global Photo Rental (GPR)**

[![Laravel](https://img.shields.io/badge/Laravel-10.x-red?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue?logo=php&logoColor=white)](https://php.net)
[![Filament](https://img.shields.io/badge/Filament-v3-green?logo=filament&logoColor=white)](https://filamentphp.com)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)

Global Photo Rental adalah platform manajemen rental peralatan fotografi terlengkap. Dari kamera mirrorless hingga lighting setup, kelola inventaris, booking, dan transaksi dengan mudah melalui admin panel modern dan API robust.

## ✨ **Fitur Utama**

-   **📦 Manajemen Inventaris Lengkap**

    -   Kategori, sub-kategori, brand (termasuk premiere brands)
    -   Produk dengan spesifikasi detail, serial numbers, dan rental includes
    -   Bundling packages untuk rental combo (misal: wedding kit)
    -   Tracking ketersediaan real-time berdasarkan tanggal

-   **💳 Sistem Rental & Transaksi**

    -   Booking rental dengan periode tanggal fleksibel
    -   Kalkulasi harga otomatis (harian, diskon promo, subtotal)
    -   Status transaksi (booking, confirmed, completed)
    -   Integrasi promo codes dan notes pelanggan

-   **🔧 Admin Panel Modern**

    -   Dibangun dengan [Filament PHP](https://filamentphp.com) - UI intuitif
    -   CRUD operations untuk semua resources (products, customers, transactions)
    -   Bulk import/export Excel/CSV dengan validasi serial numbers
    -   Memory-optimized untuk dataset besar

-   **🌐 API RESTful Siap Produksi**

    -   Public endpoints untuk browsing products, categories, brands
    -   Protected endpoints dengan API Key (rate limiting, logging)
    -   Search advanced dengan autocomplete dan suggestions
    -   Availability checks untuk cart dan multiple items

-   **📱 Integrasi Eksternal**

    -   WhatsApp API untuk notifikasi otomatis (via WAHA)
    -   Google Sheets sync untuk backup dan reporting
    -   Email verification dan custom notifications
    -   Region data (provinsi, kabupaten, kecamatan, desa) untuk alamat pelanggan

-   **⚡ Performance & Security**

    -   Optimized queries dan caching untuk speed tinggi
    -   Rate limiting, API key expiration, dan security headers
    -   Logging lengkap untuk monitoring usage dan errors
    -   Image compression dan session handling efisien

-   **🛠 Tools Pengembang**
    -   Artisan commands untuk API key management
    -   Queue jobs untuk bulk operations (imports, updates)
    -   Observers untuk auto-sync dan notifications
    -   Deployment scripts untuk server dan backup otomatis

## 🛠 **Tech Stack**

-   **Backend:** Laravel 10.x, PHP 8.1+
-   **Frontend Admin:** Filament v3 (TALL Stack: Tailwind, Alpine, Livewire)
-   **Database:** MySQL (optimized schema untuk inventory)
-   **API:** Sanctum untuk auth, Custom middleware untuk keys
-   **Integrations:** WAHA (WhatsApp), Google Sheets API, Fonnte SMS
-   **Tools:** Composer, NPM, Artisan, Queue (Redis/Database), Filament Importers/Exporters
-   **Deployment:** Laragon (local), Nginx/Apache (prod), Cron jobs untuk queues

## 🚀 **Installation**

### **Prerequisites**

-   PHP 8.1+
-   Composer
-   Node.js & NPM
-   MySQL 8.0+
-   Laragon (untuk local dev Windows) atau XAMPP/MAMP

### **Local Setup dengan Laragon**

1. **Clone Repository**

    ```bash
    git clone https://github.com/yourusername/global-photo-rental.git
    cd global-photo-rental
    ```

2. **Install Dependencies**

    ```bash
    composer install
    npm install
    npm run build
    ```

3. **Environment Setup**

    - Copy `.env.example` ke `.env`
    - Generate app key: `php artisan key:generate`
    - Konfigurasi database di `.env` (DB_HOST=127.0.0.1, DB_DATABASE=gpr_db, etc.)
    - Setup mailer (untuk notifications): Gunakan Mailtrap atau SMTP

4. **Database & Migrations**

    ```bash
    php artisan migrate --seed
    ```

5. **Jalankan Server**

    - Start Laragon (Apache + MySQL)
    - Set virtual host ke `gpr.id` (edit hosts file: 127.0.0.1 gpr.id)
    - Akses: http://gpr.id
    - Admin login: Default user dari seeder (check database)

6. **API Keys & Queues**

    ```bash
    php artisan api:key:create --name="Local Dev"
    php artisan queue:work  # Untuk background jobs
    ```

7. **Test API**
    - Base URL: http://gpr.id/api
    - Coba: `curl http://gpr.id/api/categories`

### **Production Deployment**

-   Gunakan Forge/Envoyer atau manual setup
-   Config queue supervisor untuk WhatsApp dan sync jobs
-   Enable HTTPS dan optimize .htaccess

## 📖 **Usage**

### **Admin Panel**

-   Login di http://gpr.id/admin
-   Manage products: Tambah/edit dengan foto, specs, serials
-   Transactions: View invoices, update status, export PDF
-   Customers: Import dari Excel, sync ke Google Sheets
-   Bundlings: Buat package rental custom

### **API Usage**

Lihat detail di [API Documentation](API_DOCUMENTATION.md) atau http://gpr.id/api/docs (jika setup Swagger).

Contoh frontend integration:

```javascript
fetch("http://gpr.id/api/products?category=camera", {
    headers: { Accept: "application/json" },
})
    .then((res) => res.json())
    .then((data) => console.log(data.data));
```

### **WhatsApp Integration**

-   Start WAHA server: `php artisan whatsapp:start`
-   Send notifications otomatis pada booking confirmed

## 🔍 **Screenshots**

_(Tambahkan gambar di folder assets atau gunakan GitHub images)_

-   Admin Dashboard: ![Dashboard](screenshots/dashboard.png)
-   Product Management: ![Products](screenshots/products.png)
-   Transaction View: ![Transaction](screenshots/transaction.png)

## 🤝 **Contributing**

1. Fork repo dan buat feature branch
2. Commit changes: `git commit -m "Add: New feature"`
3. Push dan buat Pull Request
4. Ikuti coding standards Laravel (PSR-12)

Issues? Buka ticket di GitHub.

## 📄 **License**

MIT License - Lihat [LICENSE](LICENSE) file.

## 👥 **Contact**

-   **Developer:** Ahmad Fauzi
-   **Email:** ahmad@globalphotorental.com
-   **Website:** https://globalphotorental.com

Terima kasih telah berkontribusi! 🚀

# Penggunaan DatabaseRetry Helper

Helper `DatabaseRetry` digunakan untuk menangani operasi database yang rentan terhadap lock timeout, terutama pada tabel `sessions` atau tabel lain dengan konkurensi tinggi.

## Contoh Penggunaan

### 1. Operasi Database Dasar

```php
use App\Helpers\DatabaseRetry;
use Illuminate\Support\Facades\DB;

// Contoh operasi update yang rentan terhadap lock timeout
$result = DatabaseRetry::run(function() use ($userId) {
    return DB::table('users')
        ->where('id', $userId)
        ->update(['last_login' => now()]);
});
```

### 2. Dengan Konfigurasi Retry Kustom

```php
use App\Helpers\DatabaseRetry;

// Menggunakan 5 percobaan dengan waktu tunggu awal 200ms
$result = DatabaseRetry::run(function() use ($sessionId, $data) {
    return DB::table('sessions')
        ->where('id', $sessionId)
        ->update(['payload' => serialize($data)]);
}, 5, 200);
```

### 3. Dalam Model atau Repository

```php
use App\Helpers\DatabaseRetry;

class UserRepository
{
    public function updateLastLogin($userId)
    {
        return DatabaseRetry::run(function() use ($userId) {
            return User::where('id', $userId)
                ->update(['last_login' => now()]);
        });
    }
}
```

### 4. Dengan Transaksi Database

```php
use App\Helpers\DatabaseRetry;
use Illuminate\Support\Facades\DB;

$result = DatabaseRetry::run(function() use ($orderId, $items) {
    return DB::transaction(function() use ($orderId, $items) {
        // Update order
        DB::table('orders')
            ->where('id', $orderId)
            ->update(['status' => 'processing']);
            
        // Insert order items
        foreach ($items as $item) {
            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);
        }
        
        return true;
    });
});
```

## Catatan Penting

1. Helper ini secara otomatis mendeteksi error "Lock wait timeout exceeded" dan hanya melakukan retry untuk error tersebut.
2. Menggunakan exponential backoff untuk mengurangi kemungkinan konflik berulang.
3. Semua retry dicatat dalam log untuk memudahkan debugging.
4. Jika semua percobaan gagal, exception asli akan dilemparkan.

## Rekomendasi Penggunaan

- Gunakan untuk operasi yang sering mengalami lock timeout
- Ideal untuk operasi pada tabel dengan konkurensi tinggi seperti `sessions`, `carts`, atau `orders`
- Pertimbangkan untuk menggunakan nilai `maxAttempts` dan `sleepMs` yang sesuai dengan kebutuhan aplikasi
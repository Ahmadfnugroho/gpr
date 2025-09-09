# Praktik Terbaik Penggunaan Redis Session

Dokumen ini berisi panduan praktik terbaik untuk menggunakan Redis sebagai driver session di aplikasi Laravel untuk menghindari masalah "Lock wait timeout exceeded".

## Konfigurasi Optimal

### 1. Konfigurasi .env

```
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_CONNECTION=session

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=gpr_session:
```

### 2. Konfigurasi Redis Khusus untuk Session

Pastikan konfigurasi database.php memiliki koneksi Redis khusus untuk session:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', ''),
        'read_timeout' => 60,
        'tcp_keepalive' => 1,
        'persistent' => true,
    ],
    
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'read_write_timeout' => 60,
    ],
    
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
        'read_write_timeout' => 60,
    ],
    
    'session' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '2'),
        'read_write_timeout' => 60,
    ],
],
```

## Praktik Terbaik Penggunaan Session

### 1. Minimalkan Data Session

Hindari menyimpan data besar dalam session:

```php
// HINDARI
Session::put('large_data', $hugeArray);

// LEBIH BAIK
Cache::put('user_' . auth()->id() . '_large_data', $hugeArray);
Session::put('large_data_key', 'user_' . auth()->id() . '_large_data');
```

### 2. Gunakan Session Flash dengan Bijak

```php
// Gunakan flash untuk data sementara
Session::flash('status', 'Operation successful!');

// Hindari flash untuk data besar
// HINDARI
Session::flash('temp_results', $largeResultSet);
```

### 3. Implementasi Retry untuk Operasi Session Kritis

```php
use App\Helpers\DatabaseRetry;

// Untuk operasi session yang kritis
DatabaseRetry::run(function() use ($data) {
    Session::put('important_data', $data);
    return Session::save();
});
```

### 4. Gunakan Session Regeneration dengan Bijak

```php
// Regenerasi session ID hanya pada titik kritis seperti login/logout
if (Auth::attempt($credentials)) {
    request()->session()->regenerate();
    // ...
}

// Saat logout
public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
}
```

### 5. Monitoring dan Logging

Pantau performa Redis dan operasi session:

```php
// Di AppServiceProvider atau middleware khusus
Session::extend('redis', function ($app) {
    $handler = new RedisSessionHandler(
        $app['redis'],
        config('session.connection'),
        config('session.lifetime'),
        $app
    );
    
    // Tambahkan logging untuk debugging
    if (config('app.debug')) {
        $handler->setReadCallback(function ($sessionId) {
            Log::debug("Reading session: {$sessionId}");
        });
        
        $handler->setWriteCallback(function ($sessionId, $data) {
            Log::debug("Writing session: {$sessionId}", [
                'size' => strlen($data)
            ]);
        });
    }
    
    return $handler;
});
```

## Troubleshooting

### 1. Periksa Koneksi Redis

```bash
redis-cli ping
```

### 2. Monitor Operasi Redis

```bash
redis-cli monitor
```

### 3. Periksa Penggunaan Memori Redis

```bash
redis-cli info memory
```

### 4. Periksa Statistik Hits/Misses

```bash
redis-cli info stats | grep keyspace
```

## Kesimpulan

Dengan mengikuti praktik terbaik ini, aplikasi Anda akan lebih tahan terhadap masalah "Lock wait timeout exceeded" saat menggunakan Redis sebagai driver session. Kombinasi konfigurasi yang tepat, pengelolaan data session yang bijak, dan mekanisme retry akan membantu meningkatkan keandalan aplikasi Anda.
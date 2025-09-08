<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\DetailTransaction;
use App\Models\DetailTransactionProductItem;

echo "🚀 AUTO-FIX: Missing Product Serial Assignments\n";
echo "==============================================\n\n";

// SAFETY CHECK - hanya jalankan di production dengan konfirmasi
if (php_sapi_name() === 'cli') {
    echo "⚠️  PERHATIAN: Script ini akan mengubah data di database!\n";
    echo "Pastikan Anda sudah backup database terlebih dahulu.\n\n";
    echo "Ketik 'YES' untuk melanjutkan: ";
    
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if ($confirmation !== 'YES') {
        echo "❌ Dibatalkan. Script tidak dijalankan.\n";
        exit;
    }
}

// Find problematic detail transactions
$problematicDetails = DetailTransaction::whereNotNull('product_id')
    ->whereDoesntHave('productItems')
    ->whereHas('transaction', function ($query) {
        $query->whereIn('booking_status', ['booking', 'paid', 'on_rented']);
    })
    ->with(['product', 'transaction'])
    ->get();

echo "🔍 Ditemukan " . $problematicDetails->count() . " detail transaksi bermasalah\n\n";

$fixedCount = 0;
$skippedCount = 0;

foreach ($problematicDetails as $detail) {
    echo "📋 Transaksi: {$detail->transaction->invoice_number}\n";
    echo "   Produk: {$detail->product->name}\n";
    
    // Cari product item yang tersedia untuk produk ini
    $availableItem = ProductItem::where('product_id', $detail->product_id)
        ->where('is_available', true)
        ->first();
    
    if ($availableItem) {
        try {
            // Buat entry di pivot table
            DetailTransactionProductItem::create([
                'detail_transaction_id' => $detail->id,
                'product_item_id' => $availableItem->id,
            ]);
            
            echo "   ✅ DIPERBAIKI: Linked dengan serial {$availableItem->serial_number}\n";
            $fixedCount++;
            
        } catch (Exception $e) {
            echo "   ❌ ERROR: " . $e->getMessage() . "\n";
            $skippedCount++;
        }
    } else {
        echo "   ⚠️  DILEWAT: Tidak ada product item tersedia\n";
        echo "      💡 Perlu buat product item dulu untuk produk ini\n";
        $skippedCount++;
    }
    
    echo "\n";
}

echo "==============================================\n";
echo "📊 HASIL:\n";
echo "   ✅ Diperbaiki: {$fixedCount} transaksi\n";
echo "   ⚠️  Dilewat: {$skippedCount} transaksi\n\n";

if ($fixedCount > 0) {
    echo "🎉 BERHASIL! Sekarang coba cek ProductAvailability lagi.\n";
    echo "   - 'Current Rentals' seharusnya menampilkan transaksi aktif\n";
    echo "   - 'No serial numbers' seharusnya berubah jadi serial number asli\n\n";
}

if ($skippedCount > 0) {
    echo "📝 UNTUK TRANSAKSI YANG DILEWAT:\n";
    echo "   1. Buat product item (serial number) untuk produk tersebut\n";
    echo "   2. Jalankan script ini lagi untuk auto-assign\n";
    echo "   3. Atau assign manual melalui form edit transaksi\n\n";
}

echo "✅ Proses selesai!\n";

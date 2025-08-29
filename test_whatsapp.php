<?php

require_once 'vendor/autoload.php';

use App\Services\WAHAService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $wahaService = new WAHAService();
    $adminPhone = '6281117095956';
    
    $message = "🧪 *TEST NOTIFIKASI*\n\n";
    $message .= "✅ WAHA Service berhasil terhubung!\n";
    $message .= "📱 WhatsApp API Global Photo Rental siap digunakan.\n";
    $message .= "🕐 Test dilakukan: " . date('d M Y H:i:s') . "\n\n";
    $message .= "🚀 Sistem notifikasi WhatsApp untuk registrasi user baru telah aktif!";
    
    echo "Mengirim test message ke WhatsApp...\n";
    
    $result = $wahaService->sendMessage($adminPhone, $message);
    
    if ($result) {
        echo "✅ Test message berhasil dikirim!\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Test message gagal dikirim.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

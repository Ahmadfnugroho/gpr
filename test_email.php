<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test basic email sending
    Mail::raw('Test email untuk mengecek konfigurasi SMTP', function ($message) {
        $message->to('imam.prabowo1511@gmail.com')
                ->subject('Test Email - Global Photo Rental');
    });
    
    echo "Email test berhasil dikirim!\n";
} catch (Exception $e) {
    echo "Error mengirim email: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

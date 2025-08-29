<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WAHAService
{
    private $baseUrl;
    private $apiKey;
    private $session;

    public function __construct()
    {
        $this->baseUrl = 'https://whatsapp.globalphotorental.com/api';
        $this->apiKey = 'gbTnWu4oBizYlgeZ0OPJlbpnG11ARjsf';
        $this->session = 'default';
    }

    /**
     * Kirim pesan WhatsApp
     */
    public function sendMessage($to, $message)
    {
        try {
            // Format nomor telepon (pastikan format internasional)
            $formattedTo = $this->formatPhoneNumber($to);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/sendText", [
                'session' => $this->session,
                'chatId' => $formattedTo . '@c.us',
                'text' => $message,
            ]);

            if ($response->successful()) {
                // Log::info('WhatsApp message sent successfully', [
                //     'to' => $formattedTo,
                //     'message_preview' => substr($message, 0, 100),
                //     'response' => $response->json()
                // ]);
                return true;
            } else {
                // Log::error('Failed to send WhatsApp message', [
                //     'to' => $formattedTo,
                //     'status' => $response->status(),
                //     'response' => $response->body()
                // ]);
                return false;
            }
        } catch (\Exception $e) {
            // Log::error('Exception sending WhatsApp message', [
            //     'to' => $to,
            //     'error' => $e->getMessage()
            // ]);
            return false;
        }
    }

    /**
     * Cek status session WAHA
     */
    public function getSessionStatus()
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
            ])->get("{$this->baseUrl}/sessions/{$this->session}");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            // Log::error('Failed to get WAHA session status', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Start session WAHA
     */
    public function startSession()
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/sessions", [
                'name' => $this->session,
                'config' => [
                    'webhooks' => []
                ]
            ]);

            if ($response->successful()) {
                // Log::info('WAHA session started successfully', ['response' => $response->json()]);
                return true;
            } else {
                // Log::error('Failed to start WAHA session', [
                //     'status' => $response->status(),
                //     'response' => $response->body()
                // ]);
                return false;
            }
        } catch (\Exception $e) {
            // Log::error('Exception starting WAHA session', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Format nomor telepon ke format internasional
     */
    private function formatPhoneNumber($phone)
    {
        // Hapus semua karakter non-digit
        $phone = preg_replace('/\D/', '', $phone);

        // Jika dimulai dengan +62, hapus +
        if (strpos($phone, '62') === 0) {
            return $phone;
        }

        // Jika dimulai dengan 0, ganti dengan 62
        if (strpos($phone, '0') === 0) {
            return '62' . substr($phone, 1);
        }

        // Jika tidak dimulai dengan 62 atau 0, tambahkan 62
        if (strpos($phone, '62') !== 0) {
            return '62' . $phone;
        }

        return $phone;
    }

    /**
     * Kirim pesan notifikasi transaksi
     */
    public function sendTransactionNotification($transaction, $eventType)
    {
        $user = $transaction->user;
        $phoneNumber = $user->userPhoneNumbers->first()?->phone_number;

        if (!$phoneNumber) {
            // Log::warning('No phone number found for user', ['user_id' => $user->id]);
            return false;
        }

        $message = $this->buildTransactionMessage($transaction, $eventType, $user->name);

        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Buat pesan notifikasi transaksi
     */
    private function buildTransactionMessage($transaction, $eventType, $userName)
    {
        $status = $transaction->booking_status;
        $transactionId = $transaction->booking_transaction_id;
        $startDate = $transaction->start_date->format('d M Y');
        $endDate = $transaction->end_date->format('d M Y');
        $total = number_format($transaction->grand_total, 0, ',', '.');

        $statusEmoji = [
            'pending' => '⏳',
            'paid' => '✅',
            'rented' => '📦',
            'finished' => '🎉',
            'cancelled' => '❌'
        ];

        $emoji = $statusEmoji[$status] ?? '📋';

        if ($eventType === 'created') {
            if ($status === 'pending') {
                $message = "🔔 *Transaksi Baru Dibuat*\n\n";
                $message .= "Halo *{$userName}*,\n\n";
                $message .= "Transaksi Anda berhasil dibuat!\n\n";
                $message .= "📋 *Detail Transaksi:*\n";
                $message .= "• ID: *{$transactionId}*\n";
                $message .= "• Status: {$emoji} *" . ucfirst($status) . "*\n";
                $message .= "• Tanggal: {$startDate} - {$endDate}\n";
                $message .= "• Total: *Rp {$total}*\n\n";
                $message .= "💰 Silakan lakukan pembayaran segera.\n\n";
                $message .= "🧾 Lihat invoice: " . url("/pdf/{$transaction->id}") . "\n\n";
                $message .= "Terima kasih telah menggunakan layanan Global Photo Rental! 📸";
            } elseif ($status === 'paid') {
                $message = "✅ *Transaksi Lunas*\n\n";
                $message .= "Halo *{$userName}*,\n\n";
                $message .= "Pembayaran Anda telah kami terima!\n\n";
                $message .= "📋 *Detail Transaksi:*\n";
                $message .= "• ID: *{$transactionId}*\n";
                $message .= "• Status: {$emoji} *LUNAS*\n";
                $message .= "• Tanggal: {$startDate} - {$endDate}\n";
                $message .= "• Total: *Rp {$total}*\n\n";
                $message .= "🎉 Selamat! Transaksi Anda telah lunas.\n\n";
                $message .= "📸 Siapkan diri Anda untuk sesi foto yang luar biasa!\n\n";
                $message .= "Terima kasih telah mempercayai Global Photo Rental! 🙏";
            }
        } elseif ($eventType === 'updated') {
            switch ($status) {
                case 'paid':
                    $message = "✅ *Pembayaran Diterima*\n\n";
                    $message .= "Halo *{$userName}*,\n\n";
                    $message .= "Pembayaran untuk transaksi *{$transactionId}* telah kami terima.\n\n";
                    $message .= "🎉 Status: *LUNAS*\n";
                    $message .= "💰 Total: *Rp {$total}*\n";
                    $message .= "📅 Periode: {$startDate} - {$endDate}\n\n";
                    $message .= "📸 Bersiaplah untuk sesi foto yang menakjubkan!\n\n";
                    $message .= "Terima kasih! 🙏";
                    break;

                case 'rented':
                    $message = "📦 *Barang Sudah Diambil*\n\n";
                    $message .= "Halo *{$userName}*,\n\n";
                    $message .= "Peralatan untuk transaksi *{$transactionId}* telah diambil.\n\n";
                    $message .= "📸 Selamat berkarya!\n";
                    $message .= "🕒 Jangan lupa kembalikan tepat waktu ya!\n\n";
                    $message .= "Periode sewa: {$startDate} - {$endDate}\n\n";
                    $message .= "Happy shooting! 📷✨";
                    break;

                case 'finished':
                    $message = "🎉 *Transaksi Selesai*\n\n";
                    $message .= "Halo *{$userName}*,\n\n";
                    $message .= "Transaksi *{$transactionId}* telah selesai!\n\n";
                    $message .= "Terima kasih telah menggunakan layanan Global Photo Rental.\n\n";
                    $message .= "💝 Semoga hasil foto/video Anda memuaskan!\n\n";
                    $message .= "⭐ Jangan lupa follow Instagram kami untuk update terbaru:\n";
                    $message .= "📱 @globalphotorental\n\n";
                    $message .= "Sampai jumpa lagi! 👋📸";
                    break;

                case 'cancelled':
                    $message = "❌ *Transaksi Dibatalkan*\n\n";
                    $message .= "Halo *{$userName}*,\n\n";
                    $message .= "Transaksi *{$transactionId}* telah dibatalkan.\n\n";
                    $message .= "Jika Anda merasa tidak melakukan pembatalan ini, segera hubungi admin.\n\n";
                    $message .= "📞 WhatsApp: +62 812-1234-9564\n";
                    $message .= "📧 Email: global.photorental@gmail.com\n\n";
                    $message .= "Terima kasih atas pengertiannya.";
                    break;

                default:
                    $message = "📋 *Update Transaksi*\n\n";
                    $message .= "Halo *{$userName}*,\n\n";
                    $message .= "Ada update pada transaksi *{$transactionId}*\n\n";
                    $message .= "Status sekarang: {$emoji} *" . ucfirst($status) . "*\n";
                    $message .= "Total: *Rp {$total}*\n\n";
                    $message .= "Terima kasih!";
            }
        }

        return $message;
    }

    /**
     * Get all sessions
     */
    public function getSessions()
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
            ])->get("{$this->baseUrl}/sessions");

            return $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            // Log::error('Failed to get WAHA sessions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get WAHA version
     */
    public function getVersion()
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
            ])->get("https://whatsapp.globalphotorental.com/version");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            // Log::error('Failed to get WAHA version', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get QR Code for connection
     */
    public function getQrCode($sessionName = 'default')
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
            ])->get("https://whatsapp.globalphotorental.com/api/{$sessionName}/auth/qr");

            if ($response->successful()) {
                // Return base64 encoded image
                $imageData = $response->body();
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            return null;
        } catch (\Exception $e) {
            // Log::error('WAHA get QR code failed', [
            //     'error' => $e->getMessage()
            // ]);
            return null;
        }
    }

    /**
     * Restart session
     */
    public function restartSession($sessionName = 'default')
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/sessions/{$sessionName}/restart");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            // Log::error('WAHA restart session failed', [
            //     'session' => $sessionName,
            //     'error' => $e->getMessage()
            // ]);
            return null;
        }
    }
}

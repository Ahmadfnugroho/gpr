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
     * 
     * @param string $to Nomor telepon penerima
     * @param string $message Pesan yang akan dikirim
     * @param string|null $sessionName Custom session name (optional)
     * @return array Response dengan status dan pesan
     */
    public function sendMessage($to, $message, $sessionName = null)
    {
        try {
            // Gunakan session name yang diberikan atau default
            $session = $sessionName ?: $this->session;
            
            // Validasi session name
            if (empty($session)) {
                $errorMessage = 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard';
                Log::error($errorMessage, ['session' => $session]);
                return ['success' => false, 'message' => $errorMessage];
            }
            
            // Format nomor telepon (pastikan format internasional)
            $formattedTo = $this->formatPhoneNumber($to);
            
            // Validasi nomor telepon
            if (empty($formattedTo)) {
                $errorMessage = 'Invalid phone number format';
                Log::error($errorMessage, ['to' => $to]);
                return ['success' => false, 'message' => $errorMessage];
            }

            Log::info('Sending WhatsApp message', [
                'session' => $session,
                'to' => $formattedTo,
                'message_length' => strlen($message)
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/sendText", [
                'session' => $session,
                'chatId' => $formattedTo . '@c.us',
                'text' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'to' => $formattedTo,
                    'session' => $session,
                    'status' => 'success'
                ]);
                return ['success' => true, 'message' => 'Message sent successfully'];
            } else {
                $errorMessage = 'Failed to send WhatsApp message';
                $errorData = [];
                
                // Handle error 422 (session not found atau validasi error lainnya)
                if ($response->status() === 422) {
                    try {
                        $errorData = $response->json();
                        $errorMessage = $errorData['message'] ?? 'Validation error';
                        
                        // Cek apakah session tidak valid
                        if (strpos(strtolower($errorMessage), 'session not found') !== false || 
                            strpos(strtolower($errorMessage), 'invalid session') !== false) {
                            $errorMessage = 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard';
                            
                            // Coba mulai session otomatis
                            Log::info('Attempting to start session automatically', ['session' => $session]);
                            $startResult = $this->startSession($session);
                            if ($startResult['success']) {
                                // Coba kirim pesan lagi setelah session dimulai
                                return $this->sendMessage($to, $message, $session);
                            }
                        }
                    } catch (\Exception $jsonEx) {
                        $errorMessage = 'Validation error: ' . $response->body();
                    }
                }
                
                Log::error($errorMessage, [
                    'to' => $formattedTo,
                    'session' => $session,
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                
                return ['success' => false, 'message' => $errorMessage, 'status' => $response->status()];
            }
        } catch (\Exception $e) {
            $errorMessage = 'Exception sending WhatsApp message: ' . $e->getMessage();
            Log::error($errorMessage, [
                'to' => $to,
                'session' => $sessionName ?: $this->session
            ]);
            return ['success' => false, 'message' => $errorMessage];
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
     * 
     * @param string $sessionName Nama session yang akan dimulai
     * @return bool|array True jika berhasil, array dengan error jika gagal
     */
    public function startSession($sessionName = null)
    {
        try {
            // Gunakan session name yang diberikan atau default
            $session = $sessionName ?: $this->session;
            
            // Validasi session name
            if (empty($session)) {
                Log::error('Invalid session name', ['session' => $session]);
                return ['success' => false, 'message' => 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard'];
            }
            
            // Endpoint untuk memulai session baru
            $endpoint = "{$this->baseUrl}/sessions";
            if ($sessionName) {
                // Endpoint untuk memulai session yang sudah ada
                $endpoint = "{$this->baseUrl}/sessions/{$session}/start";
            }
            
            Log::info('Starting WAHA session', [
                'endpoint' => $endpoint,
                'session' => $session
            ]);
            
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'name' => $session,
                'config' => [
                    'webhooks' => [
                        // Konfigurasi webhook jika diperlukan
                        // {
                        //     'url': 'https://example.com/webhook',
                        //     'events': ['message', 'message.any']
                        //     'secret': 'your_webhook_secret',
                        //     'headers': {
                        //         'Authorization': 'Basic ' . base64_encode('wahaadmin:Infrasglobal@100')
                        //     }
                        // }
                    ]
                ]
            ]);

            Log::info('Start session response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                Log::info('WAHA session started successfully', ['session' => $session]);
                return ['success' => true, 'message' => 'Session started successfully'];
            } else {
                $errorMessage = 'Failed to start session';
                $errorData = [];
                
                // Handle error 422 (session already exists or other validation errors)
                if ($response->status() === 422) {
                    try {
                        $errorData = $response->json();
                        $errorMessage = $errorData['message'] ?? 'Session validation error';
                    } catch (\Exception $jsonEx) {
                        $errorMessage = 'Session validation error: ' . $response->body();
                    }
                }
                
                Log::error($errorMessage, [
                    'session' => $session,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return ['success' => false, 'message' => $errorMessage, 'status' => $response->status()];
            }
        } catch (\Exception $e) {
            $errorMessage = 'Exception starting WAHA session: ' . $e->getMessage();
            Log::error($errorMessage, ['session' => $sessionName ?: $this->session]);
            return ['success' => false, 'message' => $errorMessage];
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
            ])->get("{$this->baseUrl}/version");

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
            // First check session status
            $sessions = $this->getSessions();
            $currentSession = null;

            foreach ($sessions as $session) {
                if ($session['name'] === $sessionName) {
                    $currentSession = $session;
                    break;
                }
            }

            // If session is already working, return message
            if ($currentSession && $currentSession['status'] === 'WORKING') {
                throw new \Exception('Session sudah terhubung ke WhatsApp. Tidak perlu scan QR code.');
            }

            // If no session exists, create one
            if (!$currentSession) {
                $this->startSession();
                // Wait a moment for session to initialize
                sleep(2);
            }

            // Try different QR endpoint formats
            $endpoints = [
                "{$this->baseUrl}/{$sessionName}/auth/qr",
            ];

            foreach ($endpoints as $endpoint) {
                Log::info('Trying QR endpoint', ['endpoint' => $endpoint]);

                $response = Http::timeout(30)->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                ])->get($endpoint);

                Log::info('QR endpoint response', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'content_type' => $response->header('content-type'),
                    'body_preview' => substr($response->body(), 0, 100)
                ]);

                if ($response->successful()) {
                    $contentType = $response->header('content-type');

                    // Check if response is an image
                    if (strpos($contentType, 'image') !== false) {
                        $imageData = $response->body();
                        Log::info('Got QR image', ['size' => strlen($imageData)]);
                        return 'data:image/png;base64,' . base64_encode($imageData);
                    }

                    // Check if response is JSON with QR data
                    try {
                        $jsonData = $response->json();
                        if (isset($jsonData['qr'])) {
                            Log::info('Got QR from JSON');
                            return $jsonData['qr'];
                        }
                    } catch (\Exception $e) {
                        // Not JSON, continue
                    }

                    // If successful but not image, try to get QR from body
                    $body = $response->body();
                    if (!empty($body) && strlen($body) > 100) {
                        // Check if it looks like base64 or image data
                        if (strpos($body, 'data:image') === 0) {
                            Log::info('Got base64 QR directly');
                            return $body;
                        } else {
                            // Assume it's raw image data
                            Log::info('Got raw image data', ['size' => strlen($body)]);
                            return 'data:image/png;base64,' . base64_encode($body);
                        }
                    }
                } else if ($response->status() === 422) {
                    // Session might be already connected or in wrong state
                    $errorBody = $response->body();
                    Log::warning('QR endpoint returned 422', ['body' => $errorBody]);
                    throw new \Exception('Session dalam status yang tidak memungkinkan untuk generate QR code. Status: ' . ($currentSession['status'] ?? 'Unknown'));
                }
            }

            Log::error('All QR endpoints failed');
            throw new \Exception('Tidak dapat mengambil QR Code dari semua endpoint yang tersedia.');
        } catch (\Exception $e) {
            Log::error('WAHA get QR code failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Restart session
     */
    public function restartSession($sessionName = 'default')
    {
        try {
            // Use the correct WAHA restart endpoint
            $endpoint = "{$this->baseUrl}/sessions/{$sessionName}/restart";

            Log::info('Restarting WAHA session', [
                'endpoint' => $endpoint,
                'session' => $sessionName
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint);

            Log::info('Restart endpoint response', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                Log::info('Session restarted successfully');
                return true;
            } else {
                Log::error('Failed to restart session', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('WAHA restart session failed', [
                'session' => $sessionName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Logout/Stop session
     */
    public function logoutSession($sessionName = 'default')
    {
        try {
            // Use the correct WAHA logout endpoint
            $endpoint = "{$this->baseUrl}/sessions/{$sessionName}/logout";

            Log::info('Logging out WAHA session', [
                'endpoint' => $endpoint,
                'session' => $sessionName
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint);

            Log::info('Logout endpoint response', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                Log::info('Session logged out successfully');
                return true;
            } else {
                Log::error('Failed to logout session', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // If logout fails, try to stop the session as fallback
                Log::warning('Logout failed, trying stop as fallback');
                $stopResponse = Http::withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}/sessions/{$sessionName}/stop");

                if ($stopResponse->successful()) {
                    Log::info('Session stopped successfully as fallback');
                    return true;
                }

                return false;
            }
        } catch (\Exception $e) {
            Log::error('WAHA logout session failed', [
                'session' => $sessionName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

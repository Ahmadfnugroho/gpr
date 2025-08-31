<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WAHAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    protected $wahaService;

    public function __construct()
    {
        $this->wahaService = new WAHAService();
    }

    /**
     * Dashboard WhatsApp Management
     */
    public function dashboard(Request $request)
    {
        try {
            // Get session status
            $sessions = $this->wahaService->getSessions();
            $version = $this->wahaService->getVersion();
            
            // Get active session from query parameter or use default
            $activeSession = $request->query('session', 'default');
            
            // Validate session name
            if (empty($activeSession) || !is_string($activeSession)) {
                $activeSession = 'default';
            }
            
            return view('admin.whatsapp.dashboard', compact('sessions', 'version', 'activeSession'));
        } catch (\Exception $e) {
            return view('admin.whatsapp.dashboard')->with('error', 'Tidak dapat terhubung ke WAHA server: ' . $e->getMessage());
        }
    }

    /**
     * Get QR Code for connection with specific session name
     */
    public function getQrCode(Request $request, $session = 'default')
    {
        // Validate session name if provided
        if ($request->has('session')) {
            $session = $request->session;
        }
        
        if (empty($session) || !is_string($session)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard'
            ], 400);
        }
        
        try {
            $qrCode = $this->wahaService->getQrCode($session);
            
            return response()->json([
                'success' => true,
                'qr_code' => $qrCode,
                'session' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check session status with specific session name
     */
    public function getSessionStatus(Request $request, $session = 'default')
    {
        // Validate session name if provided
        if ($request->has('session')) {
            $session = $request->session;
        }
        
        if (empty($session) || !is_string($session)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard'
            ], 400);
        }
        
        try {
            $sessions = $this->wahaService->getSessions();
            
            // Find the requested session
            $sessionData = null;
            foreach ($sessions as $s) {
                if ($s['id'] === $session) {
                    $sessionData = $s;
                    break;
                }
            }
            
            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => "Session '{$session}' tidak ditemukan"
                ]);
            }
            
            $status = $sessionData['status'] ?? 'UNKNOWN';
            $me = $sessionData['me'] ?? null;
            
            return response()->json([
                'success' => true,
                'session' => $session,
                'status' => $status,
                'connected' => in_array($status, ['WORKING', 'CONNECTED']),
                'me' => $me
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send test message with specific session name
     */
    public function sendTestMessage(Request $request, $session = 'default')
    {
        $request->validate([
            'phone_number' => 'required|string',
            'message' => 'required|string|max:1000'
        ]);

        // Validate session name if provided
        if ($request->has('session')) {
            $session = $request->session;
        }
        
        if (empty($session) || !is_string($session)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard'
            ], 400);
        }

        try {
            $result = $this->wahaService->sendMessage(
                $request->phone_number,
                $request->message,
                $session
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Pesan berhasil dikirim melalui session '{$session}'!"
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Gagal mengirim pesan: " . ($result['message'] ?? 'Unknown error')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Restart session with specific session name
     */
    public function restartSession(Request $request, $session = 'default')
    {
        try {
            // Validate session name
            if (empty($session) || !is_string($session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard'
                ], 400);
            }
            
            // Restart session with specified name
            $result = $this->wahaService->restartSession($session);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Session '{$session}' berhasil di-restart. Status akan kembali ke 'Scan QR Code 📱'. Silakan scan QR code baru."
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Session '{$session}' restart gagal: " . ($result['message'] ?? 'Silakan coba lagi.')
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal restart session: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Logout/End session with specific session name
     */
    public function logoutSession(Request $request, $session = 'default')
    {
        try {
            // Validate session name
            if (empty($session) || !is_string($session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard'
                ], 400);
            }
            
            // Logout/stop session with specified name
            $result = $this->wahaService->logoutSession($session);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Session '{$session}' berhasil diakhiri. WhatsApp telah logout dan akan kembali ke status 'Scan QR Code 📱'."
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Session '{$session}' logout gagal: " . ($result['message'] ?? 'Silakan coba lagi atau restart session.')
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengakhiri session: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get session logs
     */
    public function getSessionLogs()
    {
        try {
            // Get recent logs from Laravel log
            $logFile = storage_path('logs/laravel.log');
            $logs = [];
            
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $lines = array_slice($lines, -50); // Last 50 lines
                
                foreach ($lines as $line) {
                    if (strpos($line, 'WhatsApp') !== false || strpos($line, 'WAHA') !== false) {
                        $logs[] = $line;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'logs' => array_slice($logs, -20) // Last 20 WhatsApp related logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Stop WAHA server
     */
    public function stopServer()
    {
        try {
            // Stop WAHA server
            $result = $this->wahaService->stopServer();
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Server berhasil dihentikan. Semua sesi WhatsApp telah terputus.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghentikan server: ' . ($result['message'] ?? 'Unknown error')
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghentikan server: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Start session with specific session name
     */
    public function startSession(Request $request, $session = 'default')
    {
        try {
            // Validate session name
            if (empty($session) || !is_string($session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session name, check it at whatsapp.globalphotorental.com/dashboard'
                ], 400);
            }
            
            // Start session with specified name
            $result = $this->wahaService->startSession($session);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Session '{$session}' berhasil dimulai. Silakan scan QR code."
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal memulai session '{$session}': " . ($result['message'] ?? 'Unknown error')
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai session: ' . $e->getMessage()
            ]);
        }
    }
}

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
    public function dashboard()
    {
        try {
            // Get session status
            $sessions = $this->wahaService->getSessions();
            $version = $this->wahaService->getVersion();
            
            return view('admin.whatsapp.dashboard', compact('sessions', 'version'));
        } catch (\Exception $e) {
            return view('admin.whatsapp.dashboard')->with('error', 'Tidak dapat terhubung ke WAHA server: ' . $e->getMessage());
        }
    }

    /**
     * Get QR Code for connection
     */
    public function getQrCode()
    {
        try {
            $qrCode = $this->wahaService->getQrCode();
            
            return response()->json([
                'success' => true,
                'qr_code' => $qrCode
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check session status
     */
    public function getSessionStatus()
    {
        try {
            $sessions = $this->wahaService->getSessions();
            $status = $sessions[0]['status'] ?? 'UNKNOWN';
            $me = $sessions[0]['me'] ?? null;
            
            return response()->json([
                'success' => true,
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
     * Send test message
     */
    public function sendTestMessage(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'message' => 'required|string|max:1000'
        ]);

        try {
            $result = $this->wahaService->sendMessage(
                $request->phone_number,
                $request->message
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesan berhasil dikirim!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Restart session
     */
    public function restartSession()
    {
        try {
            // Restart session default
            $result = $this->wahaService->restartSession('default');
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session berhasil di-restart. Status akan kembali ke "Scan QR Code 📱". Silakan scan QR code baru.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Session restart gagal. Silakan coba lagi.'
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
     * Logout/End session
     */
    public function logoutSession()
    {
        try {
            // Logout/stop session default
            $result = $this->wahaService->logoutSession('default');
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session berhasil diakhiri. WhatsApp telah logout dan akan kembali ke status "Scan QR Code 📱".'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Session logout gagal. Silakan coba lagi atau restart session.'
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
}

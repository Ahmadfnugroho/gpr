<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected string $token;

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
    }

    public function sendMessage(string $target, string $message): void
    {
        $target = preg_replace('/[^0-9]/', '', $target); // Hapus simbol
        $target = ltrim($target, '0'); // Hapus nol depan
        $target = '62' . $target;

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $this->token,
            ])
            ->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ]);

        Log::info('Fonnte API response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FonnteService
{
    protected $token;

    public function __construct()

    {
        $this->token = env('FONNTE_TOKEN'); // pastikan FONNTE_TOKEN ada di .env
    }

    public function sendMessage($to, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token
        ])->post('https://api.fonnte.com/send', [
            'target' => $to,
            'message' => $message,
            'countryCode' => '62' // opsional, default 62
        ]);

        return $response->json();
    }
}

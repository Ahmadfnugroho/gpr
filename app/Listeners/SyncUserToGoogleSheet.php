<?php

namespace App\Listeners;

use App\Events\UserDataChanged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SyncUserToGoogleSheet
{
    public function __construct()
    {
        //
    }

    public function handle(UserDataChanged $event)
    {
        $user = $event->user->load('userPhoneNumbers');

        $payload = [
            'email' => $user->email,
            'name' => $user->name,
            'address' => $user->address,
            'job' => $user->job,
            'office_address' => $user->office_address,
            'instagram_username' => $user->instagram_username,
            'emergency_contact_name' => $user->emergency_contact_name,
            'emergency_contact_number' => $user->emergency_contact_number,
            'gender' => $user->gender,
            'source_info' => $user->source_info,
            'status' => $user->status,
            'phone_numbers' => $user->userPhoneNumbers->pluck('phone_number')->values()->toArray(),
            'updated_at' => $user->updated_at ? Carbon::parse($user->updated_at)->toIso8601String() : null,
        ];

        try {
            // Kirim data user ke Google Sheet API
            $response = Http::withHeaders([
                'x-api-key' => config('services.google_sheet.api_key')
            ])->post('https://global1.work.gd/api/google-sheet-sync', $payload);

            if ($response->successful()) {
                // Log::info('SyncUserToGoogleSheet: Successfully synced user ' . $user->email);
            } else {
                // Log::error('SyncUserToGoogleSheet: Failed to sync user ' . $user->email . '. Response: ' . $response->body());
            }

            // --- Tambahan: Trigger Google Apps Script Web App untuk import data terbaru ---
            $googleScriptUrl = 'https://script.google.com/macros/s/AKfycbzTY2p7gv9dPLmwQJHrVJoIP8lKOu3_1BDqHtlEQjvyElm6o1dEU5rT52ZthCs0CKIn/exec';

            $triggerResponse = Http::post($googleScriptUrl);

            if ($triggerResponse->successful()) {
                // Log::info('Triggered Google Apps Script importDataFromDatabase successfully.');
            } else {
                // Log::error('Failed to trigger Google Apps Script importDataFromDatabase. Response: ' . $triggerResponse->body());
            }
        } catch (\Exception $e) {
            // Log::error('SyncUserToGoogleSheet: Exception while syncing user ' . $user->email . '. Error: ' . $e->getMessage());
        }
    }
}

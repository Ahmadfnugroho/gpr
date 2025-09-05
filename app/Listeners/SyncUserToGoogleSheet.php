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
        $customer = $event->customer->load('userPhoneNumbers');

        $payload = [
            'email' => $customer->email,
            'name' => $customer->name,
            'address' => $customer->address,
            'job' => $customer->job,
            'office_address' => $customer->office_address,
            'instagram_username' => $customer->instagram_username,
            'emergency_contact_name' => $customer->emergency_contact_name,
            'emergency_contact_number' => $customer->emergency_contact_number,
            'gender' => $customer->gender,
            'source_info' => $customer->source_info,
            'status' => $customer->status,
            'phone_numbers' => $customer->userPhoneNumbers->pluck('phone_number')->values()->toArray(),
            'updated_at' => $customer->updated_at ? Carbon::parse($customer->updated_at)->toIso8601String() : null,
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

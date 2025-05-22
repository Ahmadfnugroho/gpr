<?php

namespace App\Listeners;

use App\Events\UserDataChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;

class SyncUserToGoogleSheet
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserDataChanged $event)
    {
        $user = $event->user->load('phoneNumbers');

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
            'phone_numbers' => $user->phoneNumbers->pluck('phone_number')->values()->toArray(),
            'updated_at' => $user->updated_at->toIso8601String(),
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.google_sheet.api_key')
            ])->post('https://global1.work.gd/api/google-sheet-sync', $payload);

            if ($response->successful()) {
                Log::info('SyncUserToGoogleSheet: Successfully synced user ' . $user->email);
            } else {
                Log::error('SyncUserToGoogleSheet: Failed to sync user ' . $user->email . '. Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('SyncUserToGoogleSheet: Exception while syncing user ' . $user->email . '. Error: ' . $e->getMessage());
        }
    }
}

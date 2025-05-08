<?php

namespace App\Listeners;

use App\Events\UserDataChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

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

        Http::withHeaders([
            'x-api-key' => config('services.google_sheet.api_key')
        ])->post('https://script.google.com/macros/s/AKfycbwgVwR0t7HpOCqf14TYzdYb95QISsjo3-Tj7WbXgd5kSZn08AsgjJJrmVEJWA_7fN_L/exec', $payload);
    }
}

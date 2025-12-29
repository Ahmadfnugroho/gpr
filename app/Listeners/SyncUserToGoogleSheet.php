<?php

namespace App\Listeners;

use App\Events\UserDataChanged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SyncUserToGoogleSheet
{
    /**
     * @deprecated
     * Sinkronisasi satu arah (Google Sheet → Database).
     * Listener ini sengaja dimatikan untuk mencegah infinite loop.
     */
    public function handle(UserDataChanged $event): void
    {
        // Disabled by design
        return;
    }
}

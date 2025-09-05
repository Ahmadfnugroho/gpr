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
}

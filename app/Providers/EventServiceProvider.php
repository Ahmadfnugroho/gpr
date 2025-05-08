<?php

namespace App\Providers;

use App\Events\UserDataChanged;
use App\Listeners\SyncUserToGoogleSheet;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserDataChanged::class => [
            SyncUserToGoogleSheet::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

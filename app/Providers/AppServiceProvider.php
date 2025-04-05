<?php

namespace App\Providers;

use App\Models\DetailTransaction;
use App\Observers\DetailTransactionObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paksa semua URL menggunakan HTTPS di production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        DetailTransaction::observe(DetailTransactionObserver::class);
    }
}

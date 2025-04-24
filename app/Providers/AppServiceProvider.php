<?php

namespace App\Providers;

use App\Models\DetailTransaction;
use App\Models\Transaction;
use App\Observers\DetailTransactionObserver;
use App\Observers\TransactionObserver;
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

        Transaction::observe(TransactionObserver::class);
    }
}

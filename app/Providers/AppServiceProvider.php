<?php

namespace App\Providers;

use App\Models\DetailTransaction;
use App\Models\ProductItem;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\DetailTransactionObserver;
use App\Observers\TransactionObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register CollisionServiceProvider only in local/testing environments
        if ($this->app->environment(['local', 'testing'])) {
            if (class_exists(\NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider::class)) {
                $this->app->register(\NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider::class);
            }
        }
    }

    public function boot(): void
    {
        // Set Carbon locale to Indonesian
        Carbon::setLocale('id');

        // Paksa semua URL menggunakan HTTPS di production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Custom validation rule untuk ukuran file maksimal di server
        \Illuminate\Support\Facades\Validator::extend('max_server_size', function ($attribute, $value, $parameters, $validator) {
            if (!$value instanceof \Illuminate\Http\UploadedFile) {
                return false;
            }

            $maxSize = (int) $parameters[0] * 1024; // Convert to KB
            $fileSize = $value->getSize() / 1024; // Get size in KB

            // Jika ukuran file melebihi batas server, cek apakah sudah dikompresi di client-side
            if ($fileSize > $maxSize) {
                // Jika belum dikompresi, tolak file
                return false;
            }

            return true;
        }, 'Ukuran file :attribute tidak boleh melebihi :max_server_size KB setelah kompresi.');

        DetailTransaction::observe(DetailTransactionObserver::class);
        Transaction::observe(TransactionObserver::class);

        DetailTransaction::updated(function ($detailTransaction) {
            if ($detailTransaction->isDirty('booking_status')) {
                $newStatus = $detailTransaction->booking_status;
                $oldStatus = $detailTransaction->getOriginal('booking_status');

                // Jika status berubah dari on_rented/paid ke done/cancel
                if (
                    in_array($oldStatus, ['on_rented', 'paid']) &&
                    in_array($newStatus, ['done', 'cancel'])
                ) {
                    // Kembalikan product items
                    foreach ($detailTransaction->serial_numbers as $serial) {
                        $productItem = ProductItem::where('product_id', $detailTransaction->product_id)
                            ->where('serial_number', $serial)
                            ->first();

                        if ($productItem) {
                            $productItem->update([
                                'is_available' => true,
                                'detail_transaction_id' => null
                            ]);

                            // Hapus product transaction
                        }
                    }

                    $detailTransaction->product->updateQuantity();
                }
            }
        });
        User::observe(UserObserver::class);
    }
}

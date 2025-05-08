<?php

namespace App\Providers;

use App\Models\DetailTransaction;
use App\Models\ProductItem;
use App\Models\ProductTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\DetailTransactionObserver;
use App\Observers\TransactionObserver;
use App\Observers\UserObserver;
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
        DetailTransaction::observe(DetailTransactionObserver::class);


        DetailTransaction::updated(function ($detailTransaction) {
            if ($detailTransaction->isDirty('booking_status')) {
                $newStatus = $detailTransaction->booking_status;
                $oldStatus = $detailTransaction->getOriginal('booking_status');

                // Jika status berubah dari rented/paid ke finished/cancelled
                if (
                    in_array($oldStatus, ['rented', 'paid']) &&
                    in_array($newStatus, ['finished', 'cancelled'])
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
                            ProductTransaction::where('product_item_id', $productItem->id)
                                ->delete();
                        }
                    }

                    $detailTransaction->product->updateQuantity();
                }
            }
        });
        User::observe(UserObserver::class);
    }
}

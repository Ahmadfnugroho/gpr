<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class DetailTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'bundling_id',
        'product_id',
        'quantity',
        'available_quantity',
        'price',
        'total_price',
    ];
    protected $casts = [
        'price' => 'float',
        'total_price' => 'float',
    ];


    private static function saveProductItems($detailTransaction)
    {
        $productItemIds = $detailTransaction->product_item_ids;

        if (!empty($productItemIds)) {
            // Hapus data lama di pivot table
            $detailTransaction->productItems()->detach();

            // Simpan data baru ke pivot table
            foreach ($productItemIds as $productItemId) {
                DetailTransactionProductItem::create([
                    'detail_transaction_id' => $detailTransaction->id,
                    'product_item_id' => $productItemId,
                ]);
            }
        }
    }
    public function getBundlingSerialNumbersAttribute()
    {
        $value = $this->getAttributeFromArray('bundling_serial_numbers');

        if (!is_array($value)) {
            return [];
        }

        return $value;
    }
    // Optionally, if you want to always have it available as an attribute:



    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bundling(): BelongsTo
    {
        return $this->belongsTo(Bundling::class);
    }
    public function productItems()
    {
        return $this->belongsToMany(ProductItem::class, 'detail_transaction_product_item')
            ->using(DetailTransactionProductItem::class)
            ->withTimestamps();
    }



    public function rentalIncludes(): HasManyThrough
    {
        return $this->hasManyThrough(
            RentalInclude::class,
            Product::class,
            'id',
            'include_product_id'
        );
    }
    public function getCleanedBundlingSerialNumbersAttribute()
    {
        $raw = $this->bundling_serial_numbers ?? [];

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        if (!is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(function ($item) {
                return is_array($item) ? $item : [];
            })
            ->filter(function ($item) {
                return isset($item['product_id']) && $item['product_id'];
            })
            ->values()
            ->toArray();
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($detail) {
            // Hanya untuk produk individual (non-bundling)
            if ($detail->product_id && !$detail->bundling_id && $detail->quantity > 0) {
                DB::transaction(function () use ($detail) {
                    try {
                        Log::info("Processing DetailTransaction creation", [
                            'product_id' => $detail->product_id,
                            'quantity' => $detail->quantity
                        ]);

                        // Get transaction dates
                        $transaction = Transaction::findOrFail($detail->transaction_id);
                        $startDate = $transaction->start_date;
                        $endDate = $transaction->end_date;

                        // Check availability with date range
                        $availableItems = ProductItem::where('product_id', $detail->product_id)
                            ->whereDoesntHave('detailTransactions.transaction', function ($query) use ($startDate, $endDate) {
                                $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                    ->where(function ($q) use ($startDate, $endDate) {
                                        $q->whereBetween('start_date', [$startDate, $endDate])
                                            ->orWhereBetween('end_date', [$startDate, $endDate])
                                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                                $q2->where('start_date', '<=', $startDate)
                                                    ->where('end_date', '>=', $endDate);
                                            });
                                    });
                            })
                            ->take($detail->quantity)
                            ->get();

                        if ($availableItems->count() < $detail->quantity) {
                            throw new \Exception("Tidak cukup item yang tersedia untuk periode sewa yang dipilih");
                        }

                        Log::info("Found available items", [
                            'items' => $availableItems->pluck('id')->toArray()
                        ]);

                        // Save detail transaction
                        $detail->save();

                        // Sync items dengan atomic operation
                        $itemIds = $availableItems->pluck('id')->toArray();
                        $detail->productItems()->sync($itemIds);

                        Log::info("Successfully synced items", [
                            'detail_transaction_id' => $detail->id,
                            'item_ids' => $itemIds
                        ]);

                        return true;
                    } catch (\Exception $e) {
                        Log::error("Error in DetailTransaction creation", [
                            'error' => $e->getMessage(),
                            'product_id' => $detail->product_id
                        ]);
                        throw $e;
                    }
                });

                return false; // Prevent additional save
            }
        });

        // Handle status changes
        static::updated(function ($detail) {
            if ($detail->transaction && $detail->transaction->wasChanged('booking_status')) {
                $newStatus = $detail->transaction->booking_status;

                Log::info("Transaction status changed", [
                    'detail_transaction_id' => $detail->id,
                    'new_status' => $newStatus
                ]);

                // Update product items status based on transaction status
                if (in_array($newStatus, ['done', 'cancel'])) {
                    // Mark items as available when transaction is completed or cancelled
                    $detail->productItems()->update(['is_available' => true]);
                } elseif (in_array($newStatus, ['booking', 'paid', 'on_rented'])) {
                    // Mark items as unavailable when transaction is active
                    $detail->productItems()->update(['is_available' => false]);
                }
            }
        });
    }
}

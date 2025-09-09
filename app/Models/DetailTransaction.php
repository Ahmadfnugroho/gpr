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
                // Gunakan static flag untuk mencegah rekursi tak terbatas
                static $isProcessing = false;
                
                // Jika sudah dalam proses creating, jangan lakukan lagi
                if ($isProcessing) {
                    return;
                }
                
                $isProcessing = true;
                
                try {
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

                            // Check availability with date range - gunakan chunk untuk mengurangi penggunaan memori
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
                                ->limit($detail->quantity) // Gunakan limit daripada take untuk optimasi
                                ->get();

                            if ($availableItems->count() < $detail->quantity) {
                                throw new \Exception("Tidak cukup item yang tersedia untuk periode sewa yang dipilih");
                            }

                            Log::info("Found available items", [
                                'items' => $availableItems->pluck('id')->toArray()
                            ]);

                            // Pastikan total_price dihitung dengan benar sebelum insert
                            $price = $detail->price ?? 0;
                            $quantity = $detail->quantity ?? 0;
                            $totalPrice = $price * $quantity;
                            
                            // Save detail transaction tanpa trigger creating lagi
                            DB::table('detail_transactions')->insert([
                                'transaction_id' => $detail->transaction_id,
                                'product_id' => $detail->product_id,
                                'bundling_id' => $detail->bundling_id,
                                'quantity' => $detail->quantity,
                                'available_quantity' => $detail->available_quantity,
                                'price' => $price,
                                'total_price' => $totalPrice,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            // Dapatkan ID yang baru saja dimasukkan
                            $newDetailId = DB::getPdo()->lastInsertId();
                            $detail->id = $newDetailId;

                            // Sync items dengan atomic operation dan batasi jumlah item
                            $itemIds = $availableItems->pluck('id')->toArray();
                            
                            // Insert ke pivot table secara langsung untuk menghindari query berlebihan
                            $pivotData = [];
                            foreach ($itemIds as $itemId) {
                                $pivotData[] = [
                                    'detail_transaction_id' => $newDetailId,
                                    'product_item_id' => $itemId,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                            
                            if (!empty($pivotData)) {
                                DB::table('detail_transaction_product_item')->insert($pivotData);
                            }

                            Log::info("Successfully synced items", [
                                'detail_transaction_id' => $newDetailId,
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
                } finally {
                    $isProcessing = false;
                }

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

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

    // public static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($detail) {
    //         // Hanya untuk produk individual
    //         if ($detail->product_id && !$detail->bundling_id && $detail->quantity > 0) {
    //             $availableItems = ProductItem::where('product_id', $detail->product_id)
    //                 ->where('is_available', true)
    //                 ->limit($detail->quantity)
    //                 ->get();

    //             if ($availableItems->count() < $detail->quantity) {
    //                 throw new \Exception("Tidak cukup product items yang tersedia");
    //             }

    //             // Simpan IDs ke variable temporary dalam function scope
    //             $itemIds = $availableItems->pluck('id')->toArray();

    //             // Sync items immediately to satisfy trigger constraint
    //             DB::transaction(function () use ($detail, $itemIds) {
    //                 $detail->save();
    //                 $detail->productItems()->sync($itemIds);
    //                 return false; // Prevent further save
    //             });
    //         }
    //     });
    // }
}

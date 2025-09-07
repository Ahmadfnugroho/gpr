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

        static::saved(function ($detail) {
            // Handle productItems from form data (array of IDs)
            $productItemIds = null;
            
            // Check if productItems is set in attributes (from form)
            if (isset($detail->attributes['productItems'])) {
                $productItemIds = $detail->attributes['productItems'];
            }
            // Legacy support
            elseif (isset($detail->product_item_ids) && is_array($detail->product_item_ids)) {
                $productItemIds = $detail->product_item_ids;
            }
            // Check if productItems relationship is loaded and has data
            elseif ($detail->relationLoaded('productItems') && is_array($detail->productItems)) {
                $productItemIds = $detail->productItems;
            }
            
            // Sync the relationship if we have product item IDs
            if (is_array($productItemIds) && !empty($productItemIds)) {
                $detail->productItems()->sync($productItemIds);
            }
        });
    }
}

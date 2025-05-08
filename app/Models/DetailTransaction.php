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
        'serial_numbers',
    ];

    protected $casts = [
        'price' => MoneyCast::class,
        'total_price' => MoneyCast::class,
        'serial_numbers' => 'array',


    ];

    public function getSerialNumbersFromItemsAttribute(): string
    {
        return $this->productTransactions
            ->map(fn($pt) => $pt->productItem?->serial_number)
            ->filter()
            ->join(', ');
    }

    public function updateProductItems()
    {
        $transaction = $this->transaction;

        if (!$this->product_id || empty($this->serial_numbers) || !in_array($transaction->booking_status, ['pending', 'paid', 'rented'])) {
            return;
        }

        foreach ($this->serial_numbers as $serial) {
            $productItem = ProductItem::where('product_id', $this->product_id)
                ->where('serial_number', $serial)
                ->first();

            if ($productItem && $productItem->is_available) {
                $productItem->update(['is_available' => false]);
            }
        }
    }
    public static function boot()
    {
        parent::boot();

        static::saved(function (DetailTransaction $detail) {
            if (!is_array($detail->serial_numbers)) return;

            // Hapus data lama untuk menjaga konsistensi
            ProductTransaction::where('transaction_id', $detail->transaction_id)
                ->where('detail_transaction_id', $detail->id)
                ->delete();

            foreach ($detail->serial_numbers as $serial) {
                $item = ProductItem::where('product_id', $detail->product_id)
                    ->where('serial_number', $serial)
                    ->first();

                if ($item) {
                    ProductTransaction::create([
                        'transaction_id' => $detail->transaction_id,
                        'detail_transaction_id' => $detail->id,
                        'product_item_id' => $item->id,
                        'product_id' => $detail->product_id,
                    ]);
                }
            }
        });
    }



    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bundling()
    {
        return $this->belongsTo(Bundling::class);
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

    public function productTransactions()
    {
        return $this->hasMany(ProductTransaction::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductItem extends Model
{
    use HasFactory;
    protected $casts = [
        'is_available' => 'boolean',
    ];

    protected $fillable = [
        'product_id',
        'serial_number',
        'is_available',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function detailTransaction(): BelongsTo
    {
        return $this->belongsTo(detailTransaction::class);
    }



    public function productTransactions()
    {
        return $this->hasMany(ProductTransaction::class);
    }
    public function scopeActuallyAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('detail_transaction_id')
                ->orWhereHas('detailTransaction.transaction', function ($q2) {
                    $q2->whereIn('booking_status', ['cancelled', 'finished']);
                });
        });
    }
}

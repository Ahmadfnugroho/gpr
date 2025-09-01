<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

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

    public function detailTransactions()
    {
        return $this->belongsToMany(DetailTransaction::class, 'detail_transaction_product_item')
            ->withTimestamps();
    }

    public static function getUnavailableSerialNumbersForPeriod($productId, $startDate, $endDate): array
    {
        // Ambil semua product_item_id dari detail_transactions yang beririsan tanggal
        $productItemIds = DetailTransaction::query()
            ->where('product_id', $productId)
            ->whereNotNull('product_item_id')
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                $q2->where('start_date', '<', $startDate)
                                    ->where('end_date', '>', $endDate);
                            });
                    });
            })
            ->pluck('product_item_id')
            ->filter() // hilangkan null
            ->unique()
            ->toArray();

        // Log::info('Unavailable product_item_ids', $productItemIds);

        return ProductItem::whereIn('id', $productItemIds)
            ->pluck('serial_number')
            ->toArray();
    }
    public function scopeActuallyAvailableForPeriod($query, $startDate, $endDate)
    {
        return $query->whereDoesntHave('detailTransactions.transaction', function ($q) use ($startDate, $endDate) {
            $q->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                ->where(function ($sub) use ($startDate, $endDate) {
                    $sub->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<', $startDate)
                                ->where('end_date', '>', $endDate);
                        });
                });
        });
    }
}

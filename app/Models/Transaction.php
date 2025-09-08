<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Events\TransactionUpdated;
use App\Notifications\TransactionNotification;
use App\Services\FonnteService;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Transaction extends Model
{
    use HasFactory;
    use LogsActivity;


    protected $fillable = [
        'user_id', // Legacy field - will be deprecated
        'customer_id',
        'booking_transaction_id',
        'grand_total',
        'booking_status',
        'start_date',
        'end_date',
        'duration',
        'promo_id',
        'note',
        'down_payment',
        'remaining_payment',
        'additional_fee_1_name',
        'additional_fee_1_amount',
        'additional_fee_2_name',
        'additional_fee_2_amount',
        'additional_fee_3_name',
        'additional_fee_3_amount',
        'additional_services',
        'cancellation_fee',
    ];


    protected $casts = [
        'down_payment' => MoneyCast::class,
        'remaining_payment' => MoneyCast::class,
        'grand_total' => MoneyCast::class,
        'additional_fee_1_amount' => MoneyCast::class,
        'additional_fee_2_amount' => MoneyCast::class,
        'additional_fee_3_amount' => MoneyCast::class,
        'additional_services' => 'array',
        'cancellation_fee' => MoneyCast::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'duration' => 'integer',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'customer.name',
                'booking_transaction_id',
                'grand_total',
                'booking_status',
                'start_date',
                'end_date',
                'duration',
                'promo.name',
                'note',
            ]);
    }



    public function generateUniqueBookingTrxId()
    {
        $prefix = 'GPR';
        do {
            $bookingTrxId = $prefix . mt_rand(0, 99999);
        } while (self::where('booking_transaction_id', $bookingTrxId)->exists());

        return $bookingTrxId;
    }

    protected static function booted()
    {
        static::creating(function ($transaction) {
            $transaction->booking_transaction_id = $transaction->generateUniqueBookingTrxId();
        });

        static::saving(function ($transaction) {
            if ($transaction->start_date) {
                $transaction->start_date = Carbon::parse($transaction->start_date)
                    ->format('Y-m-d H:i:s');
            }

            $duration = (int) $transaction->duration;

            if ($transaction->start_date && $duration) {
                $transaction->end_date = Carbon::parse($transaction->start_date)
                    ->addDays($duration)
                    ->format('Y-m-d H:i:s');
            }
        });


        // Event listeners for notifications
        static::created(function ($transaction) {
            // Send notification after transaction is created
            if ($transaction->customer) {
                $transaction->customer->notify(new TransactionNotification($transaction, 'created'));
            }
        });

        static::updated(function ($transaction) {
            // Send notification only if booking_status changed
            if ($transaction->wasChanged('booking_status') && $transaction->customer) {
                $transaction->customer->notify(new TransactionNotification($transaction, 'updated'));
            }
        });
    }





    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }


    public function detailTransactions(): HasMany
    {
        return $this->hasMany(DetailTransaction::class);
    }

    protected $with = ['detailTransactions.product', 'detailTransactions.bundling'];

    // Remove the accessor to avoid overriding relation loading
    // public function getDetailTransactionsAttribute()
    // {
    //     if (!array_key_exists('detailTransactions', $this->relations)) {
    //         return new Collection();
    //     }

    //     $relation = $this->relations['detailTransactions'];

    //     if (!$relation || $relation === false || !is_array($relation) && !$relation instanceof Collection) {
    //         \Illuminate\Support\Facades\Log::warning("detailTransactions relation invalid or missing on Transaction ID: {$this->id}. Returning empty collection.");
    //         return new Collection();
    //     }

    //     return $relation instanceof Collection ? $relation : new Collection($relation);
    // }

    public function setRelation($key, $value)
    {
        if ($key === 'detailTransactions' && $value === false) {
            // Log::error("Someone set detailTransactions to FALSE! ID: " . $this->id);
        }

        return parent::setRelation($key, $value);
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

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    /**
     * Calculate cancellation fee (50% of grand total)
     */
    public function calculateCancellationFee(): int
    {
        return (int) floor($this->grand_total * 0.5);
    }

    /**
     * Calculate total additional fees
     */
    public function getTotalAdditionalFees(): int
    {
        $fee1 = $this->additional_fee_1_amount ?? 0;
        $fee2 = $this->additional_fee_2_amount ?? 0;
        $fee3 = $this->additional_fee_3_amount ?? 0;

        return (int) ($fee1 + $fee2 + $fee3);
    }

    /**
     * Auto-set cancellation fee when status changes to cancel
     */
    public function setCancellationFeeOnCancel(): void
    {
        if ($this->booking_status === 'cancel' && $this->cancellation_fee === null) {
            $this->cancellation_fee = $this->calculateCancellationFee();
            $this->save();
        }
    }
}

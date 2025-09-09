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
use Illuminate\Contracts\Queue\Queueable;


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
        'down_payment' => 'integer',
        'remaining_payment' => 'integer', 
        'grand_total' => 'integer',
        'additional_fee_1_amount' => 'integer',
        'additional_fee_2_amount' => 'integer',
        'additional_fee_3_amount' => 'integer',
        'additional_services' => 'array',
        'cancellation_fee' => 'integer',
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
            
            // Let TransactionObserver handle grand_total calculation to prevent conflicts
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

    // Removed automatic eager loading to prevent relationship issues
    // protected $with = ['detailTransactions.product', 'detailTransactions.bundling'];

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
     * Get the relationships that should be queueable.
     *
     * @return array<string>
     */
    public function getQueueableRelations(): array
    {
        return [];
    }

    /**
     * Calculate total product/bundling price before any modifiers
     */
    public function getTotalBasePrice(): int
    {
        $total = 0;
        
        // Load detail transactions with their relationships if not already loaded
        if (!$this->relationLoaded('detailTransactions')) {
            $this->load(['detailTransactions.product', 'detailTransactions.bundling']);
        }
        
        // Safety check: ensure detailTransactions is a valid collection
        $details = $this->detailTransactions;
        if (!$details || (!is_array($details) && !($details instanceof \Illuminate\Database\Eloquent\Collection))) {
            \Illuminate\Support\Facades\Log::warning('Invalid detailTransactions relation in getTotalBasePrice', [
                'transaction_id' => $this->id,
                'details_type' => gettype($details),
                'details_value' => $details
            ]);
            return 0;
        }
        
        foreach ($details as $detail) {
            // Safety check: ensure $detail is a valid object
            if (!$detail || !is_object($detail)) {
                \Illuminate\Support\Facades\Log::warning('Invalid detail transaction object', [
                    'transaction_id' => $this->id,
                    'detail_type' => gettype($detail),
                    'detail_value' => $detail
                ]);
                continue;
            }
            
            $price = 0;
            $quantity = (int) ($detail->quantity ?? 1);
            
            try {
                // Handle bundling price - check if bundling exists and is not false
                if (!empty($detail->bundling_id) && $detail->bundling && is_object($detail->bundling)) {
                    $price = (int) ($detail->bundling->price ?? 0);
                } 
                // Handle individual product price - check if product exists and is not false
                elseif (!empty($detail->product_id) && $detail->product && is_object($detail->product)) {
                    $price = (int) ($detail->product->price ?? 0);
                }
                // Fallback to stored price if relationships fail
                elseif (!empty($detail->price)) {
                    $price = (int) $detail->price;
                }
                else {
                    // Log when we can't determine price
                    \Illuminate\Support\Facades\Log::warning('Unable to determine price for detail transaction', [
                        'detail_id' => $detail->id ?? 'unknown',
                        'transaction_id' => $this->id,
                        'bundling_id' => $detail->bundling_id ?? null,
                        'product_id' => $detail->product_id ?? null,
                        'bundling_type' => isset($detail->bundling) ? gettype($detail->bundling) : 'not_set',
                        'product_type' => isset($detail->product) ? gettype($detail->product) : 'not_set',
                        'stored_price' => $detail->price ?? null
                    ]);
                }
                
                $total += $price * $quantity;
                
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Error calculating price for detail transaction', [
                    'detail_id' => $detail->id ?? 'unknown',
                    'transaction_id' => $this->id,
                    'error' => $e->getMessage(),
                    'bundling_id' => $detail->bundling_id ?? null,
                    'product_id' => $detail->product_id ?? null,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Use fallback price or skip this item
                if (!empty($detail->price)) {
                    $total += (int) $detail->price * $quantity;
                }
            }
        }
        
        return $total;
    }
    
    /**
     * Calculate discount amount using PromoCalculationService
     */
    public function getDiscountAmount(): int
    {
        if (!$this->promo_id || !$this->promo) {
            return 0;
        }
        
        $totalBasePrice = $this->getTotalBasePrice();
        $duration = (int) ($this->duration ?? 1);
        
        $service = new \App\Services\PromoCalculationService();
        $result = $service->calculateDiscount($this->promo_id, $totalBasePrice, $duration);
        
        return (int) ($result['discountAmount'] ?? 0);
    }
    
    /**
     * Get detailed discount calculation
     */
    public function getDiscountDetails(): array
    {
        if (!$this->promo_id || !$this->promo) {
            return [
                'discountAmount' => 0,
                'calculationDetails' => [],
                'explanation' => 'No promo applied'
            ];
        }
        
        $totalBasePrice = $this->getTotalBasePrice();
        $duration = (int) ($this->duration ?? 1);
        
        $service = new \App\Services\PromoCalculationService();
        return $service->calculateDiscount($this->promo_id, $totalBasePrice, $duration);
    }
    
    /**
     * Calculate total additional services fees (new and legacy)
     */
    public function getTotalAdditionalServices(): int
    {
        $total = 0;
        
        // New additional services structure
        if ($this->additional_services && is_array($this->additional_services)) {
            foreach ($this->additional_services as $service) {
                if (is_array($service) && isset($service['amount'])) {
                    $total += (int) ($service['amount'] ?? 0);
                }
            }
        }
        
        // Legacy additional fees structure
        $total += (int) ($this->additional_fee_1_amount ?? 0);
        $total += (int) ($this->additional_fee_2_amount ?? 0);
        $total += (int) ($this->additional_fee_3_amount ?? 0);
        
        return $total;
    }
    
    /**
     * Get formatted list of additional services
     */
    public function getAdditionalServicesList(): array
    {
        $services = [];
        
        // New additional services structure
        if ($this->additional_services && is_array($this->additional_services)) {
            foreach ($this->additional_services as $service) {
                if (is_array($service) && isset($service['name']) && isset($service['amount']) && $service['amount'] > 0) {
                    $services[] = [
                        'name' => $service['name'],
                        'amount' => (int) $service['amount']
                    ];
                }
            }
        }
        
        // Legacy additional fees structure
        if ($this->additional_fee_1_amount && $this->additional_fee_1_amount > 0) {
            $services[] = [
                'name' => $this->additional_fee_1_name ?: 'Additional Fee 1',
                'amount' => (int) $this->additional_fee_1_amount
            ];
        }
        
        if ($this->additional_fee_2_amount && $this->additional_fee_2_amount > 0) {
            $services[] = [
                'name' => $this->additional_fee_2_name ?: 'Additional Fee 2',
                'amount' => (int) $this->additional_fee_2_amount
            ];
        }
        
        if ($this->additional_fee_3_amount && $this->additional_fee_3_amount > 0) {
            $services[] = [
                'name' => $this->additional_fee_3_name ?: 'Additional Fee 3',
                'amount' => (int) $this->additional_fee_3_amount
            ];
        }
        
        return $services;
    }
    
    /**
     * Calculate actual grand total with proper breakdown
     * Uses unified calculation method
     */
    public function calculateActualGrandTotal(): int
    {
        // For cancelled transactions, use cancellation fee instead of full amount
        if ($this->booking_status === 'cancel') {
            $cancellationFee = $this->getCancellationFee();
            $additionalServices = $this->getTotalAdditionalServices();
            return $cancellationFee + $additionalServices;
        }
        
        // Use unified calculation method
        return $this->calculateGrandTotal();
    }
    
    /**
     * Get financial breakdown for display
     */
    public function getFinancialBreakdown(): array
    {
        $basePrice = $this->getTotalBasePrice();
        $duration = max(1, (int) ($this->duration ?? 1));
        $totalWithDuration = $basePrice * $duration;
        $discountAmount = $this->getDiscountAmount();
        $additionalServices = $this->getTotalAdditionalServices();
        $actualGrandTotal = $this->calculateActualGrandTotal();
        
        return [
            'base_price' => $basePrice,
            'duration' => $duration,
            'total_with_duration' => $totalWithDuration,
            'discount_amount' => $discountAmount,
            'additional_services' => $additionalServices,
            'actual_grand_total' => $actualGrandTotal,
            'stored_grand_total' => (int) ($this->grand_total ?? 0),
            'is_cancelled' => $this->booking_status === 'cancel',
            'cancellation_fee' => $this->getCancellationFee()
        ];
    }
    
    /**
     * Calculate cancellation fee (50% of grand total)
     */
    public function getCancellationFee(): int
    {
        if ($this->booking_status !== 'cancel') {
            return 0;
        }
        
        // Use stored cancellation fee if available
        if ($this->cancellation_fee && $this->cancellation_fee > 0) {
            return (int) $this->cancellation_fee;
        }
        
        // Calculate based on stored grand total or actual calculation
        $grandTotal = $this->grand_total ?: $this->calculateActualGrandTotal();
        return (int) floor($grandTotal * 0.5);
    }
    
    /**
     * Get payment status summary
     */
    public function getPaymentStatus(): array
    {
        $actualGrandTotal = $this->calculateActualGrandTotal();
        $downPayment = (int) ($this->down_payment ?? 0);
        $remainingPayment = max(0, $actualGrandTotal - $downPayment);
        
        $status = 'unpaid';
        $statusLabel = 'Belum Lunas';
        $statusColor = 'danger';
        
        if ($this->booking_status === 'cancel') {
            $status = 'cancelled';
            $statusLabel = 'Dibatalkan';
            $statusColor = 'warning';
        } elseif ($remainingPayment <= 0) {
            $status = 'paid';
            $statusLabel = 'Lunas';
            $statusColor = 'success';
        } elseif ($downPayment > 0) {
            $status = 'partial';
            $statusLabel = 'DP Diterima';
            $statusColor = 'info';
        }
        
        return [
            'status' => $status,
            'label' => $statusLabel,
            'color' => $statusColor,
            'down_payment' => $downPayment,
            'remaining_payment' => $remainingPayment,
            'total_amount' => $actualGrandTotal,
            'percentage_paid' => $actualGrandTotal > 0 ? round(($downPayment / $actualGrandTotal) * 100, 1) : 0
        ];
    }
    
    /**
     * Legacy method compatibility - Calculate total additional fees
     */
    public function getTotalAdditionalFees(): int
    {
        return $this->getTotalAdditionalServices();
    }
    
    /**
     * Legacy method compatibility - Calculate cancellation fee
     */
    public function calculateCancellationFee(): int
    {
        return $this->getCancellationFee();
    }

    /**
     * Get detailed breakdown of total before discount for form display
     */
    public function getTotalBeforeDiscountBreakdown(): array
    {
        $breakdown = [];
        $total = 0;
        $duration = max(1, (int) ($this->duration ?? 1));
        
        foreach ($this->detailTransactions as $detail) {
            $name = '';
            $unitPrice = 0;
            $quantity = (int) ($detail->quantity ?? 1);
            
            if ($detail->bundling_id && $detail->bundling) {
                $name = $detail->bundling->name;
                $unitPrice = (int) $detail->bundling->price;
            } elseif ($detail->product_id && $detail->product) {
                $name = $detail->product->name;
                $unitPrice = (int) $detail->product->price;
            }
            
            if ($name && $unitPrice > 0) {
                $subtotal = $unitPrice * $quantity * $duration;
                $breakdown[] = [
                    'name' => $name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'duration' => $duration,
                    'subtotal' => $subtotal,
                    'formatted' => "{$name}: Rp " . number_format($unitPrice, 0, ',', '.') . 
                                  " × {$quantity}" . 
                                  " × {$duration} hari = Rp " . number_format($subtotal, 0, ',', '.')
                ];
                $total += $subtotal;
            }
        }
        
        return [
            'items' => $breakdown,
            'total' => $total,
            'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.')
        ];
    }
    
    /**
     * Get comprehensive grand total breakdown
     */
    public function getGrandTotalBreakdown(): array
    {
        $breakdown = $this->getTotalBeforeDiscountBreakdown();
        $discountAmount = $this->getDiscountAmount();
        $additionalServices = $this->getTotalAdditionalServices();
        
        $grandTotal = $breakdown['total'] - $discountAmount + $additionalServices;
        
        return [
            'base_total' => $breakdown['total'],
            'discount_amount' => $discountAmount,
            'additional_services' => $additionalServices,
            'grand_total' => $grandTotal,
            'items_breakdown' => $breakdown['items'],
            'formatted' => [
                'base_total' => 'Rp ' . number_format($breakdown['total'], 0, ',', '.'),
                'discount_amount' => 'Rp ' . number_format($discountAmount, 0, ',', '.'),
                'additional_services' => 'Rp ' . number_format($additionalServices, 0, ',', '.'),
                'grand_total' => 'Rp ' . number_format($grandTotal, 0, ',', '.')
            ]
        ];
    }
    
    /**
     * Get stored cancellation fee from database, or calculate as 50% of grand total
     * This method ensures consistency between database and display
     */
    public function getCancellationFeeAmount(): int
    {
        // Use stored cancellation fee if available
        if ($this->cancellation_fee && $this->cancellation_fee > 0) {
            return (int) $this->cancellation_fee;
        }
        
        // Calculate 50% of stored grand total (including additional services)
        $grandTotal = (int) ($this->grand_total ?? 0);
        return (int) floor($grandTotal * 0.5);
    }
    
    /**
     * Get stored grand total from database (includes additional services)
     */
    public function getStoredGrandTotalAmount(): int
    {
        return (int) ($this->grand_total ?? 0);
    }
    
    /**
     * Get stored down payment from database
     */
    public function getStoredDownPaymentAmount(): int
    {
        return (int) ($this->down_payment ?? 0);
    }
    
    /**
     * Calculate remaining payment from database values only
     */
    public function getStoredRemainingPaymentAmount(): int
    {
        $grandTotal = $this->getStoredGrandTotalAmount();
        $downPayment = $this->getStoredDownPaymentAmount();
        return max(0, $grandTotal - $downPayment);
    }
    
    /**
     * Get down payment from database ONLY - NO CALCULATIONS
     */
    public function getDownPaymentAmount(): int
    {
        return (int) ($this->down_payment ?? 0);
    }
    
    /**
     * Get remaining payment from database ONLY - NO CALCULATIONS
     */
    public function getRemainingPaymentAmount(): int
    {
        return (int) ($this->remaining_payment ?? 0);
    }
    
    /**
     * Format currency for display
     */
    public function formatCurrency(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * UNIFIED grand total calculation method
     * Formula: (base_price * duration) - discount + sum(additional_services.amount)
     * This is the SINGLE SOURCE OF TRUTH for grand_total calculation
     */
    public function calculateGrandTotal(): int
    {
        try {
            // Calculate base total with duration
            $baseTotal = $this->getTotalBasePrice();
            $duration = max(1, (int) ($this->duration ?? 1));
            $totalWithDuration = $baseTotal * $duration;
            
            // Calculate discount
            $discountAmount = $this->getDiscountAmount();
            
            // Calculate total after discount
            $totalAfterDiscount = max(0, $totalWithDuration - $discountAmount);
            
            // Add additional services
            $additionalServicesTotal = $this->getTotalAdditionalServices();
            
            // Final grand total: (base_price * duration) - discount + additional_services
            return $totalAfterDiscount + $additionalServicesTotal;
            
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error in calculateGrandTotal', [
                'transaction_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return 0;
        }
    }
    
    /**
     * Calculate and SET grand total in the model attributes
     * This method should be used to update grand_total in database
     */
    public function calculateAndSetGrandTotal(): int
    {
        $grandTotal = $this->calculateGrandTotal();
        $this->attributes['grand_total'] = $grandTotal;
        return $grandTotal;
    }
    
    /**
     * Get grand total directly from database (for display purposes)
     */
    public function getStoredGrandTotal(): int
    {
        return (int) ($this->grand_total ?? 0);
    }
    
    /**
     * Get grand total with auto-calculation fallback
     * DOES NOT override existing database values
     */
    public function getGrandTotalWithFallback(): int
    {
        // If grand_total is stored, use it
        if ($this->grand_total && $this->grand_total > 0) {
            return (int) $this->grand_total;
        }
        
        // Otherwise calculate it using unified method (but don't save to database)
        return $this->calculateGrandTotal();
    }
    
    /**
     * Calculate grand total for display purposes only (does not save to database)
     * Uses the unified calculateGrandTotal() method
     */
    public function calculateGrandTotalOnly(): int
    {
        return $this->calculateGrandTotal();
    }

    /**
     * Auto-set cancellation fee when status changes to cancel
     */
    public function setCancellationFeeOnCancel(): void
    {
        if ($this->booking_status === 'cancel' && $this->cancellation_fee === null) {
            $this->cancellation_fee = $this->getCancellationFee();
            $this->save();
        }
    }
    
    // === Accessor methods for consistent computed values ===
    
    /**
     * Get total before discount - used for display and calculations
     */
    public function getTotalBeforeDiscountAttribute(): int
    {
        $baseTotal = $this->getTotalBasePrice();
        $duration = max(1, (int) ($this->duration ?? 1));
        return $baseTotal * $duration;
    }
    
    /**
     * Get discount amount - used for display
     */
    public function getDiscountAmountAttribute(): int
    {
        return $this->getDiscountAmount();
    }
    
    /**
     * Get total after discount but before additional services
     */
    public function getTotalAfterDiscountAttribute(): int
    {
        return max(0, $this->total_before_discount - $this->discount_amount);
    }
    
    /**
     * Get additional services total - used for display
     */
    public function getAdditionalServicesTotalAttribute(): int
    {
        return $this->getTotalAdditionalServices();
    }
    
    /**
     * Get grand total - returns stored value or calculates if not available
     */
    public function getGrandTotalAttribute()
    {
        // If the attribute is set in database, return it as integer
        if (isset($this->attributes['grand_total']) && $this->attributes['grand_total'] > 0) {
            // MoneyCast will handle this, but we ensure it's an integer for calculations
            return (int) $this->attributes['grand_total'];
        }
        
        // Calculate if not stored
        return $this->calculateAndSetGrandTotal();
    }
    
    /**
     * Get remaining payment amount
     */
    public function getRemainingPaymentAttribute(): int
    {
        $grandTotal = $this->getGrandTotalWithFallback();
        $downPayment = $this->getDownPaymentAmount();
        return max(0, $grandTotal - $downPayment);
    }
    
    /**
     * Get payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        $paymentStatus = $this->getPaymentStatus();
        return $paymentStatus['label'];
    }
    
    /**
     * Get cancellation fee amount (always calculated)
     */
    public function getCancellationFeeDisplayAttribute(): int
    {
        return $this->getCancellationFeeAmount();
    }
}

<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Product extends Model
{
    use HasFactory, LogsActivity;
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_UNAVAILABLE = 'unavailable';

    protected $fillable = [
        'name',
        'price',
        'thumbnail',
        'status',
        'slug',
        'category_id',
        'brand_id',
        'sub_category_id',
        'premiere',
    ];

    protected $casts = [
        'price' => MoneyCast::class,
    ];
    protected $appends = ['is_available'];

    // public function getQuantityAttribute()
    // {
    //     // Refactor quantity to count available product items (serial numbers)
    //     return $this->availableItems()->count();
    // }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name']);
    }

    public function setNameAttribute($value)
    {
        // Jika nilai slug tidak diberikan, generate slug dari nm_produk
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }





    public static function boot()
    {
        parent::boot();



        static::created(function ($product) {
            $product->custom_id = 'produk-' . $product->id;
            $product->saveQuietly(); // Hindari triggering event lagi
        });
    }


    // app/Models/Product.php

    // public function getAvailableQuantityAttribute()
    // {
    //     $now = now();

    //     $totalSerials = $this->items()->count();

    //     $usedSerialsCount = $this->items()
    //         ->whereHas('detailTransactions.transaction', function ($q) use ($now) {
    //             $q->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
    //                 ->where('end_date', '>=', $now);
    //         })
    //         ->count();

    //     return max(0, $totalSerials - $usedSerialsCount);
    // }
    public function getAvailableQuantityForPeriod(Carbon $startDate, Carbon $endDate): int
    {
        $cacheKey = "product_availability_{$this->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate) {
            return $this->items()
                ->whereDoesntHave('detailTransactions.transaction', function ($q) use ($startDate, $endDate) {
                    $q->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                        ->where(function ($q2) use ($startDate, $endDate) {
                            $q2->whereBetween('start_date', [$startDate, $endDate])
                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                ->orWhere(function ($q3) use ($startDate, $endDate) {
                                    $q3->where('start_date', '<', $startDate)
                                        ->where('end_date', '>', $endDate);
                                });
                        });
                })
                ->count();
        });
    }
    public function getAvailableSerialNumbersForPeriod($startDate, $endDate)
    {
        $cacheKey = "product_serials_{$this->id}_{$startDate}_{$endDate}";
        
        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($startDate, $endDate) {
            return $this->items()
                ->actuallyAvailableForPeriod($startDate, $endDate)
                ->pluck('serial_number')
                ->toArray();
        });
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }



    public function rentalIncludes(): HasMany
    {
        return $this->hasMany(RentalInclude::class);
    }

    public function productSpecifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class);
    }

    public function productPhotos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class);
    }

    public function detailTransactions(): HasMany
    {
        return $this->hasMany(DetailTransaction::class);
    }


    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class, // Model tujuan (Transaction)
            DetailTransaction::class, // Model perantara (DetailTransaction)
            'product_id', // Foreign key di tabel DetailTransaction
            'id', // Foreign key di tabel Transaction
            'id', // Local key di tabel Product
            'transaction_id' // Local key di tabel DetailTransaction
        );
    }


    public function bundlings()
    {
        return $this->belongsToMany(Bundling::class, 'bundling_products', 'product_id', 'bundling_id')->withPivot('id', 'quantity');
    }



    public function rentalIncludeTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            RentalInclude::class,
            Product::class,
            'id', // foreign key di Product
            'include_product_id' // foreign key di RentalInclude
        );
    }

    public function items()
    {
        return $this->hasMany(ProductItem::class);
    }

    public function availableItems()
    {
        $now = now();
        return $this->items()->where('is_available', true)
            ->whereDoesntHave('detailTransactions.transaction', function ($query) use ($now) {
                $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                    ->where('end_date', '>=', $now);
            });
    }

    public function getIsAvailableAttribute(): bool
    {
        // Cache availability status for 10 minutes
        $cacheKey = "product_availability_{$this->id}_" . now()->format('Y-m-d_H');
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $today = Carbon::today();
            $endOfDay = Carbon::today()->endOfDay();

            // Optimized query with exists() for better performance
            return $this->items()
                ->where('is_available', true)
                ->whereDoesntHave('detailTransactions.transaction', function ($query) use ($today, $endOfDay) {
                    $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                        ->where('start_date', '<=', $endOfDay)
                        ->where('end_date', '>=', $today);
                })
                ->exists();
        });
    }

    public function getStatusAttribute($value): string
    {
        // Cache status calculation for better performance
        $cacheKey = "product_status_{$this->id}_" . now()->format('Y-m-d_H');
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($value) {
            // Use database value if explicitly set, otherwise calculate from availability
            if ($value && in_array($value, [self::STATUS_AVAILABLE, self::STATUS_UNAVAILABLE, 'maintenance'])) {
                return $value;
            }
            
            return $this->is_available
                ? self::STATUS_AVAILABLE
                : self::STATUS_UNAVAILABLE;
        });
    }
}

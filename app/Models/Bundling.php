<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

use Spatie\Activitylog\Traits\LogsActivity;

class Bundling extends Model
{
    use LogsActivity, HasFactory;

    protected $fillable = ['name', 'price', 'slug', 'premiere', 'custom_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'premiere']);
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

        static::creating(function ($bundling) {
            if (empty($bundling->custom_id)) {
                $bundling->custom_id = 'bundling-' . (self::max('id') + 1);
            }
        });
    }

    public function detailTransactions()
    {
        return $this->hasMany(DetailTransaction::class);
    }

    public function transactions()
    {
        return $this->hasManyThrough(
            Transaction::class,
            DetailTransaction::class,
            'bundling_id',
            'id',
            'id',
            'transaction_id'
        );
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'bundling_products', 'bundling_id', 'product_id')
            ->withPivot('id', 'quantity')
            ->withTimestamps();
    }

    public function bundlingProducts()
    {
        return $this->hasMany(BundlingProduct::class, 'bundling_id');
    }

    public function bundlingPhotos()
    {
        return $this->hasMany(BundlingPhoto::class, 'bundling_id');
    }

    public function rentalIncludes()
    {
        return $this->hasManyThrough(
            RentalInclude::class,
            BundlingProduct::class,
            'bundling_id',
            'product_id',
            'id',
            'product_id'
        );
    }

    /**
     * Get all items from products in this bundling
     * This is a compatibility method for UnifiedInventoryResource
     */
    public function items()
    {
        // Use bundlingProducts instead to avoid SQL conflict
        $productIds = $this->bundlingProducts()->pluck('product_id');
        return \App\Models\ProductItem::whereIn('product_id', $productIds);
    }

    /**
     * Cek apakah bundling tersedia untuk disewa di periode tertentu
     */
    public function isAvailableForRental($startDate, $endDate, $bundlingQty = 1)
    {
        // Load bundlingProducts with product relationship to avoid SQL conflicts
        $this->load('bundlingProducts.product');
        
        foreach ($this->bundlingProducts as $bundlingProduct) {
            $needed = $bundlingProduct->quantity * $bundlingQty;
            $product = $bundlingProduct->product;

            // Hitung jumlah item tersedia di semua produk dalam bundling
            $available = $product->items()
                ->actuallyAvailableForPeriod($startDate, $endDate)
                ->count();

            if ($available < $needed) {
                return false;
            }
        }

        return true;
    }

    /**
     * Menghitung jumlah bundling yang bisa disewa di periode tertentu
     */
    public function getAvailableQuantityForPeriod(Carbon $startDate, Carbon $endDate, int $requestedQty = 1): int
    {
        $minAvailable = null;
        
        // Load bundlingProducts with product relationship to avoid SQL conflicts
        $this->load('bundlingProducts.product');

        foreach ($this->bundlingProducts as $bundlingProduct) {
            $requiredPerBundle = $bundlingProduct->quantity;
            $product = $bundlingProduct->product;

            // Ambil serial number yang tersedia untuk produk
            $availableSerials = $product->getAvailableSerialNumbersForPeriod($startDate, $endDate);

            // Hitung berapa maksimal bundling yang bisa dibuat dari serial yang tersedia
            $maxQtyForThisProduct = intdiv(count($availableSerials), $requiredPerBundle);

            $minAvailable = is_null($minAvailable)
                ? $maxQtyForThisProduct
                : min($minAvailable, $maxQtyForThisProduct);
        }

        return $minAvailable ?? 0;
    }
    public function getBundlingSerialNumbersForPeriod(Carbon $startDate, Carbon $endDate, int $bundleQty): array
    {
        $result = [];
        
        // Load bundlingProducts with product relationship to avoid SQL conflicts
        $this->load('bundlingProducts.product');

        foreach ($this->bundlingProducts as $bundlingProduct) {
            $product = $bundlingProduct->product;
            $requiredQty = $bundlingProduct->quantity * $bundleQty;

            $availableSerials = $product->getAvailableSerialNumbersForPeriod($startDate, $endDate);

            $result[] = [
                'product_id' => $product->id,
                'product_name_display' => $product->name,
                'max_serial_quantity' => $bundlingProduct->quantity,
                'product_item_ids' => array_slice($availableSerials, 0, $requiredQty),
            ];
        }

        return $result;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Promo extends Model
{
    use LogsActivity;

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($promo) {
            // Auto-generate code if not provided
            if (empty($promo->code)) {
                $promo->code = static::generateUniqueCode();
            }
        });
    }

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'rules',
        'value',
        'min_transaction',
        'max_discount',
        'valid_from',
        'valid_until',
        'active',
    ];

    protected $casts = [
        'rules' => 'array', // Untuk menyimpan aturan diskon dalam JSON
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Generate a unique promo code.
     */
    public static function generateUniqueCode(): string
    {
        do {
            // Generate code with format PROMO-XXXXXX (6 random uppercase characters)
            $code = 'PROMO-' . Str::upper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'code',
                'description',
                'type',
                'rules',
                'value',
                'valid_from',
                'valid_until',
                'active',
            ]);
    }

    public function calculateDiscountedDays(int $duration, string $dayOfWeek = null): int
    {
        if ($this->type === 'day_based') {
            // Aturan diskon berbasis hari (contoh: sewa 2 hari bayar 1 hari)
            $groupSize = $this->rules['group_size'] ?? 1; // Default 1 hari
            $payDays = $this->rules['pay_days'] ?? $groupSize; // Default bayar penuh

            $discountedDays = (int) ($duration / $groupSize) * $payDays;
            $remainingDays = $duration % $groupSize;

            return $discountedDays + $remainingDays; // Hari yang dibayar total
        }

        if ($this->type === 'percentage') {
            // Diskon persentase tidak mempengaruhi jumlah hari yang dibayar
            return $duration;
        }

        // Jika tidak ada aturan diskon
        return $duration;
    }

    /**
     * Hitung total diskon persentase.
     */
    public function calculatePercentageDiscount(float $total, string $dayOfWeek = null): float
    {
        if ($this->type === 'percentage') {
            $percentage = $this->rules['percentage'] ?? 0; // Default 0%
            $applicableDays = $this->rules['days'] ?? []; // Hari berlaku diskon

            // Terapkan diskon hanya jika hari berlaku atau hari kosong (semua hari berlaku)
            if (empty($applicableDays) || ($dayOfWeek && in_array($dayOfWeek, $applicableDays))) {
                return $total * ($percentage / 100);
            }
        }

        return 0; // Tidak ada diskon persentase
    }



    public function Transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }
}

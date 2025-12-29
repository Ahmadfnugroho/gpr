<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasApiTokens, Notifiable, LogsActivity;

    /* =========================
     | Status Constants
     ========================= */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLACKLIST = 'blacklist';

    public const AVAILABLE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_BLACKLIST,
    ];

    /* =========================
     | Mass Assignment
     | (PROFILE ONLY)
     ========================= */
    protected $fillable = [
        'name',
        'email',
        'address',
        'job',
        'office_address',
        'instagram_username',
        'facebook_username',
        'emergency_contact_name',
        'emergency_contact_number',
        'gender',
        'source_info',
        'status',
    ];

    /* =========================
     | Hidden (AUTH ONLY)
     ========================= */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /* =========================
     | Casts
     ========================= */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* =========================
     | Activity Log
     ========================= */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'address',
                'job',
                'status',
            ])
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    /* =========================
     | Relationships
     ========================= */
    public function customerPhotos(): HasMany
    {
        return $this->hasMany(CustomerPhoto::class);
    }

    public function customerPhoneNumbers(): HasMany
    {
        return $this->hasMany(CustomerPhoneNumber::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /* =========================
     | Derived Attributes
     ========================= */
    public function getPhoneNumberAttribute(): ?string
    {
        if (! $this->relationLoaded('customerPhoneNumbers')) {
            return null;
        }

        return $this->customerPhoneNumbers->first()?->phone_number;
    }

    /* =========================
     | Auth Utilities
     ========================= */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}

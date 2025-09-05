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

    // Customer Status Constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLACKLIST = 'blacklist';

    public const AVAILABLE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_BLACKLIST,
    ];

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
        self::STATUS_BLACKLIST => 'Blacklist',
    ];

    protected $fillable = [
        'name',
        'google_id',
        'email',
        'email_verified_at',
        'password',
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

    protected $with = ['customerPhotos', 'customerPhoneNumbers'];

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customerPhotos(): HasMany
    {
        return $this->hasMany(CustomerPhoto::class, 'customer_id', 'id');
    }

    public function customerPhoneNumbers(): HasMany
    {
        return $this->hasMany(CustomerPhoneNumber::class, 'customer_id', 'id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'customer_id', 'id');
    }

    // Accessor untuk phone_number dari relasi customerPhoneNumbers
    public function getPhoneNumberAttribute(): ?string
    {
        return $this->customerPhoneNumbers->first()?->phone_number;
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }
}

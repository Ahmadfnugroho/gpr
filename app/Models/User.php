<?php

namespace App\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'google_id',

        'email',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
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
            ]);
    }

    // public function setPasswordAttribute($value)
    // {
    //     do {
    //         $password = mt_rand(1000000, 9999999);
    //     } while (self::where('password', Hash::make($password))->exists());

    //     return $password;
    // }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];



    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessFilament(): bool
    {
        return true;
    }



    public function userPhotos(): HasMany
    {
        return $this->hasMany(UserPhoto::class, 'user_id', 'id');
    }


    public function userPhoneNumbers(): HasMany
    {
        return $this->hasMany(UserPhoneNumber::class, 'user_id', 'id');
    }


    public function Transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserPhoto extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'photo_type',
        'photo',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user.name',
                'photo_type',
                'photo',
            ]);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

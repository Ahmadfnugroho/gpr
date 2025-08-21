<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BundlingPhoto extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;



    protected $fillable = [
        'bundling_id',
        'photo',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['bundling.name']);
    }

    public function bundling(): BelongsTo
    {
        return $this->belongsTo(Bundling::class);
    }
}

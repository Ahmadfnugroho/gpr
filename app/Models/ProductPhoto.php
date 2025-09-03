<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductPhoto extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;



    protected $fillable = [
        'product_id',
        'photo',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product.name']);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

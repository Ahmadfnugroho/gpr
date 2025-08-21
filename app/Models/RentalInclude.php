<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class RentalInclude extends Model
{

    use SoftDeletes;
    use LogsActivity;


    protected $fillable = [
        'product_id',
        'include_product_id',
        'quantity'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product.name', 'includedProduct.name']);
    }


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function includedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'include_product_id');
    }
}

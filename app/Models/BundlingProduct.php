<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundlingProduct extends Model
{
    protected $table = 'bundling_products';
    public $timestamps = true;

    protected $fillable = [
        'bundling_id',
        'product_id',
        'quantity',
    ];

    public function bundling()
    {
        return $this->belongsTo(Bundling::class, 'bundling_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

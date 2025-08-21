<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DetailTransactionProductItem extends Pivot
{
    protected $table = 'detail_transaction_product_item';

    protected $fillable = [
        'detail_transaction_id',
        'product_item_id',
    ];

    public $timestamps = true;

    public function detailTransaction()
    {
        return $this->belongsTo(DetailTransaction::class, 'detail_transaction_id');
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class, 'product_item_id');
    }
}

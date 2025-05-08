<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ProductTransaction extends Model
{

    protected $fillable = [
        'product_id',
        'transaction_id',
        'detail_transaction_id',
        'product_item_id',


    ];

    protected $with = ['product', 'transaction'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function detailTransaction()
    {
        return $this->belongsTo(DetailTransaction::class, 'detail_transaction_id');
    }
    public static function createWithDetailTransaction($data, $detailTransaction)
    {
        // Memasukkan detail_transaction_id
        $data['detail_transaction_id'] = $detailTransaction->id;

        // Membuat entri product_transaction baru
        $productTransaction = self::create($data);

        // Log untuk verifikasi
        Log::info('Created ProductTransaction:', $productTransaction->toArray());

        return $productTransaction;
    }
}

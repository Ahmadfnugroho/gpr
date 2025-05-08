<?php

namespace App\Observers;

use App\Models\DetailTransaction;
use App\Models\ProductItem;
use App\Models\ProductTransaction;

class DetailTransactionObserver
{
    public function saved(DetailTransaction $detail)
    {
        // Hapus semua yang lama
        $detail->productTransactions()->delete();

        if (!is_array($detail->serial_numbers)) {
            return;
        }

        foreach ($detail->serial_numbers as $serialNumber) {
            $item = ProductItem::where('serial_number', $serialNumber)->first();
            if (!$item) continue;

            ProductTransaction::create([
                'transaction_id' => $detail->transaction_id,
                'detail_transaction_id' => $detail->id,
                'product_item_id' => $item->id,
                'product_id' => $detail->product_id,
            ]);
        }
    }
}

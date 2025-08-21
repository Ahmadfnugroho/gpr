<?php

namespace App\Observers;

use App\Models\DetailTransaction;

class DetailTransactionObserver
{
    public function saved(DetailTransaction $detailTransaction): void
    {
        $ids = $detailTransaction->product_item_ids;

        if (is_array($ids) && !empty($ids)) {
            $detailTransaction->productItems()->sync($ids);
        }
    }
}

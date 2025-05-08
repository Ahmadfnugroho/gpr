<?php

namespace App\Services;

use App\Models\ProductTransaction;
use App\Models\Transaction;

class ProductTransactionService
{
    /**
     * Save product item selections for a transaction.
     *
     * @param Transaction $transaction
     * @param array $detailTransactionsData
     *      Array of detail transactions data, each containing 'id' and 'selected_product_item_ids' keys.
     * @return void
     */
    public function saveProductItems(Transaction $transaction, array $detailTransactionsData): void
    {
        // Delete existing product transactions for this transaction
        ProductTransaction::where('transaction_id', $transaction->id)->delete();

        foreach ($detailTransactionsData as $detailData) {
            $detailId = $detailData['id'] ?? null;
            $selectedProductItemIds = $detailData['selected_product_item_ids'] ?? [];

            foreach ($selectedProductItemIds as $productItemId) {
                ProductTransaction::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $detailData['product_id'] ?? null,
                    'product_item_id' => $productItemId,
                ]);
            }
        }
    }
}

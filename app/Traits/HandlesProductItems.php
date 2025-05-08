<?php

namespace App\Traits;

use App\Services\ProductTransactionService;
use App\Models\Transaction;

trait HandlesProductItems
{
    protected ProductTransactionService $productTransactionService;

    public function initializeHandlesProductItems(): void
    {
        $this->productTransactionService = app(ProductTransactionService::class);
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['detailTransactions']) && is_array($data['detailTransactions'])) {
            foreach ($data['detailTransactions'] as &$detail) {
                if (isset($detail['product_item_ids'])) {
                    $detail['selected_product_item_ids'] = $detail['product_item_ids'];
                    unset($detail['product_item_ids']);
                }
            }
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $transaction = $this->record;

        $detailTransactionsData = $transaction->detailTransactions()->get()->map(function ($detail) {
            return [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'selected_product_item_ids' => $detail->selected_product_item_ids ?? [],
            ];
        })->toArray();

        $this->productTransactionService->saveProductItems($transaction, $detailTransactionsData);
    }
}

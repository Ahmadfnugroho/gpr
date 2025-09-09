<?php

namespace App\Observers;

use App\Models\Transaction;

class TransactionObserver
{
    /**
     * Handle the Transaction "saving" event.
     * DATABASE VALUES ONLY - NO CALCULATIONS OR OVERRIDES
     * Preserves all values exactly as they are set
     */
    public function saving(Transaction $transaction): void
    {
        // DO NOTHING - Let database values be exactly as they are set
        // No automatic calculations or overrides
        // This ensures down_payment and remaining_payment are preserved exactly as entered
    }

    /**
     * Handle the Transaction "saved" event.
     * DATABASE VALUES ONLY - NO CALCULATIONS OR OVERRIDES
     */
    public function saved(Transaction $transaction): void
    {
        // DO NOTHING - Let database values be exactly as they are set
        // No automatic calculations or overrides
        // This ensures all values remain exactly as stored in database
    }
}

<?php

namespace App\Console\Commands;

use App\Models\DetailTransaction;
use Illuminate\Console\Command;

class FixDetailTransactionData extends Command
{
    protected $signature = 'fix:detail-transactions';
    protected $description = 'Memperbaiki data JSON yang tidak valid di detailTransactions';

    public function handle()
    {
        DetailTransaction::chunkById(200, function ($transactions) {
            foreach ($transactions as $t) {
                // Perbaiki bundling_serial_numbers
                if (!empty($t->bundling_serial_numbers)) {
                    $data = json_decode($t->bundling_serial_numbers, true);

                    if (!is_array($data)) {
                        $data = [];
                    }

                    $cleaned = collect($data)->map(function ($item) {
                        return is_array($item) ? $item : [];
                    })->filter(fn($item) => !empty($item['product_id']))->toArray();

                    $t->update([
                        'bundling_serial_numbers' => json_encode($cleaned),
                    ]);
                }
            }
        });

        $this->info('Semua data detailTransactions telah diperbaiki.');
    }
}

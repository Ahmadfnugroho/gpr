<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;

class UpdateExpiredTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update transactions that are expired (past end_date) and still pending to finished status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Transaction::updateExpiredTransactions();
        $this->info('Expired transactions updated successfully.');
        return 0;
    }
}

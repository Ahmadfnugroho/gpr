<?php

namespace App\Console\Commands;

use App\Models\DetailTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOrphanedRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-orphaned-records {--fix : Fix orphaned records by setting foreign keys to null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for orphaned detail transaction records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for orphaned detail transactions...');
        
        // Check for orphaned bundling references
        $orphanedBundlings = DetailTransaction::whereNotNull('bundling_id')
            ->leftJoin('bundlings', 'detail_transactions.bundling_id', '=', 'bundlings.id')
            ->whereNull('bundlings.id')
            ->count();
            
        $this->line("Orphaned bundling references: {$orphanedBundlings}");
        
        // Check for orphaned product references  
        $orphanedProducts = DetailTransaction::whereNotNull('product_id')
            ->leftJoin('products', 'detail_transactions.product_id', '=', 'products.id')
            ->whereNull('products.id')
            ->count();
            
        $this->line("Orphaned product references: {$orphanedProducts}");
        
        // Show examples if any exist
        if ($orphanedBundlings > 0) {
            $this->warn("\nFirst 5 orphaned bundling records:");
            $examples = DetailTransaction::select('detail_transactions.*')
                ->whereNotNull('bundling_id')
                ->leftJoin('bundlings', 'detail_transactions.bundling_id', '=', 'bundlings.id')
                ->whereNull('bundlings.id')
                ->limit(5)
                ->get();
                
            foreach ($examples as $example) {
                $this->line("Detail ID: {$example->id}, Bundling ID: {$example->bundling_id}, Transaction ID: {$example->transaction_id}");
            }
            
            if ($this->option('fix')) {
                $this->info('\nFixing orphaned bundling references...');
                $fixed = DB::table('detail_transactions')
                    ->whereNotNull('bundling_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('bundlings')
                              ->whereRaw('bundlings.id = detail_transactions.bundling_id');
                    })
                    ->update(['bundling_id' => null]);
                $this->info("Fixed {$fixed} orphaned bundling references.");
            }
        }
        
        if ($orphanedProducts > 0) {
            $this->warn("\nFirst 5 orphaned product records:");
            $examples = DetailTransaction::select('detail_transactions.*')
                ->whereNotNull('product_id')
                ->leftJoin('products', 'detail_transactions.product_id', '=', 'products.id')
                ->whereNull('products.id')
                ->limit(5)
                ->get();
                
            foreach ($examples as $example) {
                $this->line("Detail ID: {$example->id}, Product ID: {$example->product_id}, Transaction ID: {$example->transaction_id}");
            }
            
            if ($this->option('fix')) {
                $this->info('\nFixing orphaned product references...');
                $fixed = DB::table('detail_transactions')
                    ->whereNotNull('product_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('products')
                              ->whereRaw('products.id = detail_transactions.product_id');
                    })
                    ->update(['product_id' => null]);
                $this->info("Fixed {$fixed} orphaned product references.");
            }
        }
        
        if ($orphanedBundlings === 0 && $orphanedProducts === 0) {
            $this->info('✅ No orphaned records found!');
        } elseif (!$this->option('fix')) {
            $this->info('\nRun with --fix option to automatically fix orphaned records.');
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Bundling;
use Illuminate\Console\Command;

class ListBundlings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:bundlings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all bundlings with product counts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bundlings = Bundling::withCount('products')->get();
        
        $this->table(
            ['ID', 'Name', 'Slug', 'Products Count'],
            $bundlings->map(function ($bundling) {
                return [
                    $bundling->id,
                    $bundling->name,
                    $bundling->slug,
                    $bundling->products_count
                ];
            })->toArray()
        );
        
        // Show bundling_products table entries
        $bundlingProductsCount = \DB::table('bundling_products')->count();
        $this->info("Total bundling_products entries: {$bundlingProductsCount}");
        
        if ($bundlingProductsCount > 0) {
            $bundlingProducts = \DB::table('bundling_products')->limit(10)->get();
            $this->info('Sample bundling_products entries:');
            $this->table(
                ['ID', 'Bundling ID', 'Product ID', 'Quantity'],
                $bundlingProducts->map(function ($bp) {
                    return [
                        $bp->id ?? 'N/A',
                        $bp->bundling_id,
                        $bp->product_id,
                        $bp->quantity
                    ];
                })->toArray()
            );
        }
    }
}

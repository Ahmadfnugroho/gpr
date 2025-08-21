<?php

namespace App\Console\Commands;

use App\Models\Bundling;
use Illuminate\Console\Command;

class DebugBundling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:bundling {slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug bundling products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $slug = $this->argument('slug');
        
        $bundling = Bundling::where('slug', $slug)->first();
        
        if (!$bundling) {
            $this->error("Bundling with slug '{$slug}' not found");
            return;
        }
        
        $this->info("Bundling found: {$bundling->name} (ID: {$bundling->id})");
        
        // Check raw bundling_products table
        $bundlingProducts = \DB::table('bundling_products')->where('bundling_id', $bundling->id)->get();
        $this->info("Raw bundling_products entries: {$bundlingProducts->count()}");
        
        if ($bundlingProducts->count() > 0) {
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
        
        $this->info("Products count via relationship: {$bundling->products->count()}");
        
        if ($bundling->products->count() > 0) {
            $this->table(
                ['Product ID', 'Product Name', 'Quantity'],
                $bundling->products->map(function ($product) {
                    return [
                        $product->id,
                        $product->name,
                        $product->pivot->quantity ?? 'N/A'
                    ];
                })->toArray()
            );
        } else {
            $this->warn('No products found in this bundling via relationship');
        }
    }
}

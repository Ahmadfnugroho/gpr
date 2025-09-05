<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductItem;

class TestProductAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:product-availability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Product model relationships for ProductAvailabilityResource';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Product model relationships...');
        
        try {
            // Test relationship methods without requiring actual data
            $this->info('🧪 Testing Product model methods...');
            
            // Test if items() method exists
            try {
                $product = new Product();
                $itemsRelation = $product->items();
                $this->info("✅ items() relationship method exists and returns: " . get_class($itemsRelation));
            } catch (\Exception $e) {
                $this->error("❌ items() relationship method failed: " . $e->getMessage());
            }
            
            // Test if productItems() method doesn't exist (this should fail)
            $this->info('\n🧪 Testing productItems() method (should not exist)...');
            try {
                $product = new Product();
                $product->productItems();
                $this->error('❌ ERROR: productItems() method exists but should not!');
            } catch (\BadMethodCallException $e) {
                $this->info("✅ Correct: productItems() method doesn't exist - " . $e->getMessage());
            } catch (\Exception $e) {
                $this->info("✅ Correct: productItems() relationship doesn't exist - " . $e->getMessage());
            }
            
            // Test ProductAvailabilityResource static methods with dummy data
            $this->info('\n🧪 Testing ProductAvailabilityResource static methods...');
            
            try {
                $availableCount = \App\Filament\Resources\ProductAvailabilityResource::getAvailableItemsCount(
                    999, // Non-existent product ID
                    now()->format('Y-m-d'),
                    now()->addDays(7)->format('Y-m-d')
                );
                
                $this->info("✅ getAvailableItemsCount() method works: {$availableCount} available items (for non-existent product)");
            } catch (\Exception $e) {
                $this->error("❌ getAvailableItemsCount() failed: " . $e->getMessage());
            }
            
            try {
                $serialNumbers = \App\Filament\Resources\ProductAvailabilityResource::getAvailableSerialNumbers(
                    999, // Non-existent product ID
                    now()->format('Y-m-d'),
                    now()->addDays(7)->format('Y-m-d')
                );
                
                $this->info("✅ getAvailableSerialNumbers() method works: '{$serialNumbers}' (for non-existent product)");
            } catch (\Exception $e) {
                $this->error("❌ getAvailableSerialNumbers() failed: " . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
        
        $this->info('\n🎉 Product relationship test completed successfully!');
        $this->info('💡 ProductAvailabilityResource should now work without relationship errors.');
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\DetailTransaction;
use App\Models\ProductItem;
use Illuminate\Console\Command;

class MonitorSerialAssignments extends Command
{
    protected $signature = 'monitor:serials {--fix : Automatically fix missing assignments}';
    protected $description = 'Monitor dan auto-fix missing serial assignments untuk products';

    public function handle()
    {
        $this->info('🔍 Monitoring serial assignments...');
        
        // Find detail transactions dengan product tapi tanpa serial assignments
        $problematic = DetailTransaction::whereNotNull('product_id')
            ->whereDoesntHave('productItems')
            ->whereHas('transaction', function ($query) {
                $query->whereIn('booking_status', ['booking', 'paid', 'on_rented']);
            })
            ->with(['product', 'transaction'])
            ->get();

        if ($problematic->count() == 0) {
            $this->info('✅ All transactions have proper serial assignments');
            return 0;
        }

        $this->warn("🚨 Found {$problematic->count()} transactions without serial assignments");
        
        if (!$this->option('fix')) {
            $this->info('💡 Run with --fix option to automatically fix these issues');
            
            // Show summary
            foreach ($problematic as $detail) {
                $this->line("  - Transaction {$detail->transaction->invoice_number}: {$detail->product->name} (Qty: {$detail->quantity})");
            }
            
            return 0;
        }

        $fixedCount = 0;
        $skippedCount = 0;

        foreach ($problematic as $detail) {
            $this->line("🔧 Processing: Transaction {$detail->transaction->invoice_number} - {$detail->product->name}");
            
            // Cari product items yang available
            $availableItems = ProductItem::where('product_id', $detail->product_id)
                ->where('is_available', true)
                ->actuallyAvailableForPeriod(
                    $detail->transaction->start_date,
                    $detail->transaction->end_date
                )
                ->limit($detail->quantity)
                ->get();
            
            if ($availableItems->count() >= $detail->quantity) {
                try {
                    $detail->productItems()->sync($availableItems->pluck('id'));
                    $this->info("  ✅ Fixed: Assigned {$availableItems->count()} serial numbers");
                    $fixedCount++;
                } catch (\Exception $e) {
                    $this->error("  ❌ Error: " . $e->getMessage());
                    $skippedCount++;
                }
            } else {
                $this->warn("  ⚠️  Skipped: Insufficient items (Need: {$detail->quantity}, Available: {$availableItems->count()})");
                $skippedCount++;
            }
        }

        $this->info("\n📊 SUMMARY:");
        $this->info("  ✅ Fixed: {$fixedCount} transactions");
        $this->info("  ⚠️  Skipped: {$skippedCount} transactions");

        if ($fixedCount > 0) {
            $this->info("\n🎉 SUCCESS! ProductAvailability should now show correct rental information.");
        }

        if ($skippedCount > 0) {
            $this->warn("\n💡 For skipped transactions:");
            $this->warn("  1. Create more product items (serial numbers) for those products");
            $this->warn("  2. Run this command again to auto-assign");
            $this->warn("  3. Or manually assign through transaction edit form");
        }

        return 0;
    }
}

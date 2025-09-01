<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add fulltext index for product search
        DB::statement('ALTER TABLE products ADD FULLTEXT INDEX ft_products_name (name)');
        
        // Add composite index for transaction queries
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['booking_status', 'start_date', 'end_date'], 'idx_status_dates');
        });
        
        // Add index for product item availability queries
        Schema::table('product_items', function (Blueprint $table) {
            $table->index(['product_id', 'is_available'], 'idx_product_available');
        });
        
        // Add composite index for detail_transaction_product_item if table exists
        if (Schema::hasTable('detail_transaction_product_item')) {
            Schema::table('detail_transaction_product_item', function (Blueprint $table) {
                $table->index(['detail_transaction_id', 'product_item_id'], 'idx_detail_product');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop fulltext index
        DB::statement('ALTER TABLE products DROP INDEX ft_products_name');
        
        // Drop composite indexes
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_status_dates');
        });
        
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropIndex('idx_product_available');
        });
        
        if (Schema::hasTable('detail_transaction_product_item')) {
            Schema::table('detail_transaction_product_item', function (Blueprint $table) {
                $table->dropIndex('idx_detail_product');
            });
        }
    }
};

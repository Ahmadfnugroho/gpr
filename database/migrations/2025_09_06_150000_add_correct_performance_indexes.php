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
        // Helper method to check if index exists
        $indexExists = function ($table, $indexName) {
            try {
                $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
                return !empty($indexes);
            } catch (Exception $e) {
                return false;
            }
        };

        // Helper method to check if column exists
        $columnExists = function ($table, $columnName) {
            try {
                $columns = DB::select("DESCRIBE `{$table}`");
                foreach ($columns as $column) {
                    if ($column->Field === $columnName) {
                        return true;
                    }
                }
                return false;
            } catch (Exception $e) {
                return false;
            }
        };

        // Products table indexes
        Schema::table('products', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('products', 'products_category_id_index')) {
                $table->index(['category_id'], 'products_category_id_index');
            }
            if (!$indexExists('products', 'products_brand_id_index')) {
                $table->index(['brand_id'], 'products_brand_id_index');
            }
            if (!$indexExists('products', 'products_sub_category_id_index')) {
                $table->index(['sub_category_id'], 'products_sub_category_id_index');
            }
        });

        // Product items table indexes
        Schema::table('product_items', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('product_items', 'product_items_product_id_index')) {
                $table->index(['product_id'], 'product_items_product_id_index');
            }
            if (!$indexExists('product_items', 'product_items_is_available_index')) {
                $table->index(['is_available'], 'product_items_is_available_index');
            }
        });

        // Detail transactions table indexes (check column names first)
        Schema::table('detail_transactions', function (Blueprint $table) use ($indexExists, $columnExists) {
            if (!$indexExists('detail_transactions', 'detail_transactions_product_id_index')) {
                $table->index(['product_id'], 'detail_transactions_product_id_index');
            }
            
            // Only create index if transaction_id column exists (not product_item_id)
            if ($columnExists('detail_transactions', 'transaction_id') && 
                !$indexExists('detail_transactions', 'detail_transactions_transaction_id_index')) {
                $table->index(['transaction_id'], 'detail_transactions_transaction_id_index');
            }
        });

        // Transactions table indexes
        Schema::table('transactions', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('transactions', 'transactions_booking_status_index')) {
                $table->index(['booking_status'], 'transactions_booking_status_index');
            }
            if (!$indexExists('transactions', 'transactions_start_date_index')) {
                $table->index(['start_date'], 'transactions_start_date_index');
            }
            if (!$indexExists('transactions', 'transactions_end_date_index')) {
                $table->index(['end_date'], 'transactions_end_date_index');
            }
        });

        echo "\n✅ Performance indexes checked and created where needed.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper method to check if index exists
        $indexExists = function ($table, $indexName) {
            try {
                $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
                return !empty($indexes);
            } catch (Exception $e) {
                return false;
            }
        };

        Schema::table('products', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('products', 'products_category_id_index')) {
                $table->dropIndex('products_category_id_index');
            }
            if ($indexExists('products', 'products_brand_id_index')) {
                $table->dropIndex('products_brand_id_index');
            }
            if ($indexExists('products', 'products_sub_category_id_index')) {
                $table->dropIndex('products_sub_category_id_index');
            }
        });

        Schema::table('product_items', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('product_items', 'product_items_product_id_index')) {
                $table->dropIndex('product_items_product_id_index');
            }
            if ($indexExists('product_items', 'product_items_is_available_index')) {
                $table->dropIndex('product_items_is_available_index');
            }
        });

        Schema::table('detail_transactions', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('detail_transactions', 'detail_transactions_product_id_index')) {
                $table->dropIndex('detail_transactions_product_id_index');
            }
            if ($indexExists('detail_transactions', 'detail_transactions_transaction_id_index')) {
                $table->dropIndex('detail_transactions_transaction_id_index');
            }
        });

        Schema::table('transactions', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('transactions', 'transactions_booking_status_index')) {
                $table->dropIndex('transactions_booking_status_index');
            }
            if ($indexExists('transactions', 'transactions_start_date_index')) {
                $table->dropIndex('transactions_start_date_index');
            }
            if ($indexExists('transactions', 'transactions_end_date_index')) {
                $table->dropIndex('transactions_end_date_index');
            }
        });
    }
};

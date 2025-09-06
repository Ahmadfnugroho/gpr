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
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
            return !empty($indexes);
        };

        // Products table indexes
        Schema::table('products', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('products', 'products_category_id_index')) {
                $table->index(['category_id']);
            }
            if (!$indexExists('products', 'products_brand_id_index')) {
                $table->index(['brand_id']);
            }
            if (!$indexExists('products', 'products_sub_category_id_index')) {
                $table->index(['sub_category_id']);
            }
        });

        // Product items table indexes
        Schema::table('product_items', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('product_items', 'product_items_product_id_index')) {
                $table->index(['product_id']);
            }
            if (!$indexExists('product_items', 'product_items_is_available_index')) {
                $table->index(['is_available']);
            }
        });

        // Detail transactions table indexes
        Schema::table('detail_transactions', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('detail_transactions', 'detail_transactions_product_id_index')) {
                $table->index(['product_id']);
            }
            if (!$indexExists('detail_transactions', 'detail_transactions_product_item_id_index')) {
                $table->index(['product_item_id']);
            }
        });

        // Transactions table indexes
        Schema::table('transactions', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('transactions', 'transactions_booking_status_index')) {
                $table->index(['booking_status']);
            }
            if (!$indexExists('transactions', 'transactions_start_date_index')) {
                $table->index(['start_date']);
            }
            if (!$indexExists('transactions', 'transactions_end_date_index')) {
                $table->index(['end_date']);
            }
        });

        echo "\n✅ Performance indexes checked and created where needed.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop indexes if they exist
        $indexExists = function ($table, $indexName) {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
            return !empty($indexes);
        };

        Schema::table('products', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('products', 'products_category_id_index')) {
                $table->dropIndex(['category_id']);
            }
            if ($indexExists('products', 'products_brand_id_index')) {
                $table->dropIndex(['brand_id']);
            }
            if ($indexExists('products', 'products_sub_category_id_index')) {
                $table->dropIndex(['sub_category_id']);
            }
        });

        Schema::table('product_items', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('product_items', 'product_items_product_id_index')) {
                $table->dropIndex(['product_id']);
            }
            if ($indexExists('product_items', 'product_items_is_available_index')) {
                $table->dropIndex(['is_available']);
            }
        });

        Schema::table('detail_transactions', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('detail_transactions', 'detail_transactions_product_id_index')) {
                $table->dropIndex(['product_id']);
            }
            if ($indexExists('detail_transactions', 'detail_transactions_product_item_id_index')) {
                $table->dropIndex(['product_item_id']);
            }
        });

        Schema::table('transactions', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('transactions', 'transactions_booking_status_index')) {
                $table->dropIndex(['booking_status']);
            }
            if ($indexExists('transactions', 'transactions_start_date_index')) {
                $table->dropIndex(['start_date']);
            }
            if ($indexExists('transactions', 'transactions_end_date_index')) {
                $table->dropIndex(['end_date']);
            }
        });
    }
};

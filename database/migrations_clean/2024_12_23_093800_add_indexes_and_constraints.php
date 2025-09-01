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

        // Add fulltext search index
        DB::statement('ALTER TABLE products ADD FULLTEXT INDEX ft_products_name (name)');
        
        // Add additional performance indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('premiere', 'idx_products_premiere');
            $table->index('status', 'idx_products_status');
            $table->index(['category_id', 'brand_id'], 'idx_products_category_brand');
        });
        
        Schema::table('categories', function (Blueprint $table) {
            $table->index('slug', 'idx_categories_slug');
        });
        
        Schema::table('brands', function (Blueprint $table) {
            $table->index('slug', 'idx_brands_slug');
        });
        
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->index('slug', 'idx_sub_categories_slug');
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['booking_status', 'start_date', 'end_date'], 'idx_status_dates');
        });
        
        Schema::table('product_items', function (Blueprint $table) {
            $table->index(['product_id', 'is_available'], 'idx_product_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};

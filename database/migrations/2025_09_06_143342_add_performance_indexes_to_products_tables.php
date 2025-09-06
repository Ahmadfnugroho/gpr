<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status']);
            $table->index(['category_id']);
            $table->index(['brand_id']);
            $table->index(['sub_category_id']);
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->index(['product_id']);
            $table->index(['is_available']);
        });

        Schema::table('detail_transactions', function (Blueprint $table) {
            $table->index(['product_id']);
            $table->index(['product_item_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['booking_status']);
            $table->index(['start_date']);
            $table->index(['end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['brand_id']);
            $table->dropIndex(['sub_category_id']);
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['is_available']);
        });

        Schema::table('detail_transactions', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['product_item_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['booking_status']);
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
        });
    }
};
            //

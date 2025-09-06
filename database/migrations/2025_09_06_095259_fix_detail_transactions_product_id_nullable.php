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
        // First, check if bundling_id column exists, if not add it
        Schema::table('detail_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('detail_transactions', 'bundling_id')) {
                $table->unsignedBigInteger('bundling_id')->nullable()->after('product_id');
                $table->foreign('bundling_id')->references('id')->on('bundlings')->onDelete('cascade');
            }
        });

        // Make product_id nullable and drop its foreign key constraint
        Schema::table('detail_transactions', function (Blueprint $table) {
            // Drop the existing foreign key constraint on product_id
            $table->dropForeign(['product_id']);
        });
        
        // Modify product_id to be nullable and add the foreign key back
        Schema::table('detail_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
        
        // Add columns that might be missing
        Schema::table('detail_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('detail_transactions', 'available_quantity')) {
                $table->unsignedInteger('available_quantity')->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('detail_transactions', 'price')) {
                $table->unsignedInteger('price')->nullable()->after('available_quantity');
            }
            if (!Schema::hasColumn('detail_transactions', 'total_price')) {
                $table->unsignedInteger('total_price')->nullable()->after('price');
            }
            // Remove unit_price if it exists (renamed to price)
            if (Schema::hasColumn('detail_transactions', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_transactions', function (Blueprint $table) {
            // Revert product_id to NOT NULL
            $table->dropForeign(['product_id']);
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            // Remove bundling_id column if we added it
            if (Schema::hasColumn('detail_transactions', 'bundling_id')) {
                $table->dropForeign(['bundling_id']);
                $table->dropColumn('bundling_id');
            }
        });
    }
};

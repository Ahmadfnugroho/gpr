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
        Schema::table('detail_transactions', function (Blueprint $table) {
            // Make sure product_id is nullable for bundling transactions
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_transactions', function (Blueprint $table) {
            // Revert back to NOT NULL (only if you're sure all records have product_id)
            // $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};

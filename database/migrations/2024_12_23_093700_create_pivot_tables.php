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

        // Detail transaction product items (many-to-many)
        Schema::create('detail_transaction_product_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['detail_transaction_id', 'product_item_id'], 'idx_detail_product');
            $table->unique(['detail_transaction_id', 'product_item_id'], 'idx_detail_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_transaction_product_item');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('detail_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('bundling_id')->nullable()->constrained('bundlings')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('available_quantity');
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('total_price')->default(0);
            $table->json('serial_numbers')->nullable()->comment('Nomor seri untuk setiap unit produk');

            $table->timestamps();
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_transactions');
    }
};

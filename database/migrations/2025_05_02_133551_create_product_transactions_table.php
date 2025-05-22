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
        Schema::create('product_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('detail_transaction_id'); // Pastikan kolom ini ada
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreign('detail_transaction_id')
                ->references('id')->on('detail_transactions')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_transactions');
        Schema::table('product_transactions', function (Blueprint $table) {
            // Menghapus foreign key dan kolom detail_transaction_id
            $table->dropForeign(['detail_transaction_id']);
            $table->dropColumn('detail_transaction_id');
        });
    }
};

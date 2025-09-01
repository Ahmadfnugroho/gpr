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

        // Transactions table (CONSOLIDATED - includes ALL fields from separate migrations)
        // Original: 2024_12_23_093214_create_transactions_table.php
        // Merged: 2025_09_01_210938_add_additional_services_to_transactions_table.php
        // Merged: 2025_09_01_213414_add_customer_id_to_transactions_table.php
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable(); // From add_customer_id migration - FK added later
            $table->unsignedBigInteger('promo_id')->nullable(); // FK to promos - constraint added later
            $table->string('booking_transaction_id')->unique();
            
            // Financial fields
            $table->unsignedInteger('grand_total')->nullable();
            $table->unsignedInteger('down_payment')->nullable();
            $table->unsignedInteger('remaining_payment')->nullable();
            $table->unsignedInteger('cancellation_fee')->nullable();
            
            // Booking details
            $table->enum('booking_status', ['booking', 'paid', 'on_rented', 'done', 'cancel'])->default('booking');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->unsignedInteger('duration');
            $table->text('note')->nullable();
            
            // Additional services (From add_additional_services migration)
            $table->json('additional_services')->nullable();
            
            // Additional fee fields (legacy structure)
            $table->string('additional_fee_1_name')->nullable();
            $table->unsignedInteger('additional_fee_1_amount')->nullable();
            $table->string('additional_fee_2_name')->nullable();
            $table->unsignedInteger('additional_fee_2_amount')->nullable();
            $table->string('additional_fee_3_name')->nullable();
            $table->unsignedInteger('additional_fee_3_amount')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Performance indexes
            $table->index('booking_transaction_id');
            $table->index(['booking_status', 'start_date', 'end_date']);
            $table->index('user_id');
            $table->index('customer_id');
        });

        // Detail transactions
        Schema::create('detail_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('total_price');
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->index(['transaction_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_includes');
        Schema::dropIfExists('detail_transactions');
        Schema::dropIfExists('transactions');
    }
};

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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('promo_id')->nullable()->constrained('promos')->cascadeOnDelete()->nullable();
            $table->string('booking_transaction_id');
            $table->unsignedInteger('grand_total')->nullable();
            $table->unsignedInteger('down_payment')->nullable();
            $table->unsignedInteger('remaining_payment')->nullable();
            $table->enum('booking_status', ['booking', 'paid', 'on_rented', 'done', 'cancel'])->default('booking');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->unsignedInteger('duration');
            $table->text('note')->nullable();
            
            // Additional fee fields
            $table->string('additional_fee_1_name')->nullable();
            $table->unsignedInteger('additional_fee_1_amount')->nullable();
            $table->string('additional_fee_2_name')->nullable();
            $table->unsignedInteger('additional_fee_2_amount')->nullable();
            $table->string('additional_fee_3_name')->nullable();
            $table->unsignedInteger('additional_fee_3_amount')->nullable();
            
            // Cancellation fee for 50% payment logic
            $table->unsignedInteger('cancellation_fee')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

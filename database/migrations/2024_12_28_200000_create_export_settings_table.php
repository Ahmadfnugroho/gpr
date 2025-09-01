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
        Schema::create('export_settings', function (Blueprint $table) {
            $table->id();
            $table->string('resource_name'); // e.g., 'TransactionResource'
            $table->json('column_settings'); // Store which columns to include/exclude
            $table->timestamps();
            
            $table->unique('resource_name');
        });
        
        // Insert default settings for TransactionResource
        DB::table('export_settings')->insert([
            'resource_name' => 'TransactionResource',
            'column_settings' => json_encode([
                'excluded_columns' => ['serial_numbers', 'customer_phone'],
                'included_columns' => [
                    'booking_transaction_id',
                    'customer_name',
                    'customer_email',
                    'product_info',
                    'start_date',
                    'end_date',
                    'duration',
                    'grand_total',
                    'down_payment',
                    'remaining_payment',
                    'booking_status',
                    'promo_applied',
                    'additional_services_info',
                    'cancellation_fee',
                    'note',
                    'created_at'
                ]
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_settings');
    }
};

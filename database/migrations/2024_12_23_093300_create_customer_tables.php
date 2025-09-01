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

        // Customers table (consolidated)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('google_id')->nullable()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255)->default('123456789');
            
            // Address and personal info
            $table->text('address')->nullable();
            $table->string('job')->nullable();
            $table->text('office_address')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            
            // Social media and contacts
            $table->string('instagram_username')->nullable();
            $table->string('facebook_username')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            
            // Status and source
            $table->string('source_info')->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklist'])->default('blacklist');
            
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('status');
        });

        // Customer photos
        Schema::create('customer_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('photo_type'); // profile, id_card, etc.
            $table->string('photo');
            $table->string('id_type')->nullable(); // KTP, SIM, etc.
            $table->timestamps();
            
            $table->index(['customer_id', 'photo_type']);
        });

        // Customer phone numbers
        Schema::create('customer_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 20);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index(['customer_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_phone_numbers');
        Schema::dropIfExists('customer_photos');
        Schema::dropIfExists('customers');
    }
};

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
        // Drop old user phone and photo tables if they exist (they are now replaced by customer tables)
        Schema::dropIfExists('user_phone_numbers');
        Schema::dropIfExists('user_photos');
        
        // Ensure customer_phone_numbers table exists with correct structure
        if (!Schema::hasTable('customer_phone_numbers')) {
            Schema::create('customer_phone_numbers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $table->string('phone_number', 20);
                $table->timestamps();
                
                $table->index(['customer_id']);
            });
        }
        
        // Ensure customer_photos table exists with correct structure
        if (!Schema::hasTable('customer_photos')) {
            Schema::create('customer_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $table->string('photo_type')->nullable(); // 'ktp', 'additional_id_1', 'additional_id_2', etc.
                $table->string('photo'); // Path to the photo file
                $table->string('id_type', 100)->nullable(); // Type of additional ID (SIM, NPWP, etc.)
                $table->timestamps();
                
                $table->index(['customer_id']);
                $table->index(['photo_type']);
            });
        }
        
        // Clean up User table - remove fields that are now handled by Customer model
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Drop columns that are no longer needed for admin/staff users
                $table->dropColumn([
                    'address',
                    'job', 
                    'office_address',
                    'instagram_username',
                    'facebook_username',
                    'emergency_contact_name',
                    'emergency_contact_number',
                    'gender',
                    'source_info',
                    'status',
                    'google_id'
                ]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate user_phone_numbers table
        Schema::create('user_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('phone_number', 20);
            $table->timestamps();
        });
        
        // Recreate user_photos table
        Schema::create('user_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('photo_type')->nullable();
            $table->string('photo');
            $table->string('id_type', 100)->nullable();
            $table->timestamps();
        });
        
        // Add back columns to users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('address')->nullable();
                $table->string('job')->nullable();
                $table->text('office_address')->nullable();
                $table->string('instagram_username')->nullable();
                $table->string('facebook_username')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_number', 20)->nullable();
                $table->enum('gender', ['male', 'female'])->nullable();
                $table->string('source_info')->nullable();
                $table->enum('status', ['active', 'inactive', 'blacklist'])->default('blacklist');
                $table->string('google_id')->nullable();
            });
        }
        
        // Drop customer tables
        Schema::dropIfExists('customer_photos');
        Schema::dropIfExists('customer_phone_numbers');
    }
};

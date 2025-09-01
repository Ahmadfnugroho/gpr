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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('google_id')->nullable()->index();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255)->default('123456789');
            $table->text('address')->nullable();
            $table->string('job')->nullable();
            $table->text('office_address')->nullable();
            $table->string('instagram_username')->nullable();
            $table->string('facebook_username')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('source_info')->nullable();
            $table->string('status')->default('blacklist');
            // Possible values: 'active', 'inactive', 'blacklist', and can be extended later

            $table->index('name');
            $table->index('email');
            $table->index('status');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

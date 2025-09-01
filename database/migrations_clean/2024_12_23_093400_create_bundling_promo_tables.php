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

        // Promos table
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed']);
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_transaction')->nullable();
            $table->unsignedInteger('max_discount')->nullable();
            $table->datetime('valid_from');
            $table->datetime('valid_until');
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index(['active', 'valid_from', 'valid_until']);
        });

        // Bundlings table
        Schema::create('bundlings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('active');
        });

        // Bundling products (pivot table)
        Schema::create('bundling_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundling_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            
            $table->index(['bundling_id', 'product_id']);
        });

        // Bundling photos
        Schema::create('bundling_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundling_id')->constrained('bundlings')->cascadeOnDelete();
            $table->string('photo');
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('bundling_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
        Schema::dropIfExists('bundling_photos');
        Schema::dropIfExists('bundling_products');
        Schema::dropIfExists('bundlings');
    }
};

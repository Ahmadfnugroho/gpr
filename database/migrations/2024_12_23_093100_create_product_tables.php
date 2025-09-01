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

        // Products table (consolidated with all fields)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('custom_id')->unique();
            $table->string('slug')->unique();
            $table->unsignedInteger('price');
            $table->string('thumbnail')->nullable();
            $table->enum('status', ['available', 'unavailable']);
            $table->boolean('premiere')->default(false);
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('name');
            $table->index('slug');
            $table->index('status');
            $table->index('premiere');
            $table->index(['category_id', 'brand_id']);
        });

        // Product items (serial numbers)
        Schema::create('product_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number')->unique();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'is_available']);
            $table->index('serial_number');
        });

        // Product photos
        Schema::create('product_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('photo');
            $table->timestamps();
            
            $table->index('product_id');
        });

        // Product specifications
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('value')->nullable();
            $table->timestamps();
            
            $table->index('product_id');
        });

        // Rental includes
        Schema::create('rental_includes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('include_product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->string('include_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('include_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('product_photos');
        Schema::dropIfExists('product_items');
        Schema::dropIfExists('products');
    }
};

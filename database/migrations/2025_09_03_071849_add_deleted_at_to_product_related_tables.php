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
        // Add deleted_at to sub_categories table
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add deleted_at to product_specifications table
        Schema::table('product_specifications', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add deleted_at to product_photos table
        Schema::table('product_photos', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add deleted_at to rental_includes table
        Schema::table('rental_includes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove deleted_at from sub_categories table
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove deleted_at from product_specifications table
        Schema::table('product_specifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove deleted_at from product_photos table
        Schema::table('product_photos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove deleted_at from rental_includes table
        Schema::table('rental_includes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

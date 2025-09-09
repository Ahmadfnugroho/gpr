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
        // Update the type column to support all promo types
        DB::statement("ALTER TABLE promos MODIFY COLUMN type ENUM('percentage', 'fixed', 'nominal', 'day_based') NOT NULL DEFAULT 'percentage'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE promos MODIFY COLUMN type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage'");
    }
};

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
        // Check if the column doesn't exist before adding it
        if (!Schema::hasColumn('promos', 'rules')) {
            Schema::table('promos', function (Blueprint $table) {
                $table->json('rules')->nullable()->after('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the column exists before dropping it
        if (Schema::hasColumn('promos', 'rules')) {
            Schema::table('promos', function (Blueprint $table) {
                $table->dropColumn('rules');
            });
        }
    }
};

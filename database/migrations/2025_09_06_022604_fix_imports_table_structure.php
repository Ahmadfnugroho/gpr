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
        // Check if imports table exists and fix the structure if needed
        if (Schema::hasTable('imports')) {
            // Check the current column type
            $columns = DB::select('DESCRIBE imports');
            $totalRowsColumn = collect($columns)->where('Field', 'total_rows')->first();
            
            if ($totalRowsColumn && str_contains($totalRowsColumn->Type, 'json')) {
                // Column is JSON, we need to change it to unsigned integer
                Schema::table('imports', function (Blueprint $table) {
                    $table->dropColumn(['total_rows', 'processed_rows', 'successful_rows']);
                });
                
                Schema::table('imports', function (Blueprint $table) {
                    $table->unsignedInteger('processed_rows')->default(0)->after('importer');
                    $table->unsignedInteger('total_rows')->after('processed_rows');
                    $table->unsignedInteger('successful_rows')->default(0)->after('total_rows');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a fix migration, so we don't need to reverse it
    }
};

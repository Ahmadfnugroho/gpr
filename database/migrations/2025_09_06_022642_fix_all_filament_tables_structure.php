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
        // Fix imports table if it has JSON columns instead of integers
        $this->fixTableStructure('imports');
        
        // Fix exports table if it has JSON columns instead of integers
        $this->fixTableStructure('exports');
        
        // Ensure failed_import_rows table exists with correct structure
        if (!Schema::hasTable('failed_import_rows')) {
            Schema::create('failed_import_rows', function (Blueprint $table) {
                $table->id();
                $table->json('data');
                $table->foreignId('import_id')->constrained()->cascadeOnDelete();
                $table->text('validation_error')->nullable();
                $table->timestamps();
            });
        }
    }
    
    private function fixTableStructure(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            try {
                $columns = DB::select("DESCRIBE {$tableName}");
                $needsFix = false;
                
                foreach ($columns as $column) {
                    if (in_array($column->Field, ['total_rows', 'processed_rows', 'successful_rows']) 
                        && str_contains($column->Type, 'json')) {
                        $needsFix = true;
                        break;
                    }
                }
                
                if ($needsFix) {
                    // Drop and recreate the problematic columns
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropColumn(['total_rows', 'processed_rows', 'successful_rows']);
                    });
                    
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->unsignedInteger('processed_rows')->default(0);
                        $table->unsignedInteger('total_rows');
                        $table->unsignedInteger('successful_rows')->default(0);
                    });
                }
            } catch (\Exception $e) {
                // If there's any error, skip this table
                \Log::warning("Could not fix {$tableName} structure: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a fix migration, we don't reverse structural fixes
    }
};

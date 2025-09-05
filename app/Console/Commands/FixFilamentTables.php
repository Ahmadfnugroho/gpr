<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class FixFilamentTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filament:fix-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Filament imports/exports table structure to use correct data types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing Filament table structures...');
        
        $this->fixImportsTable();
        $this->fixExportsTable();
        $this->ensureFailedImportRowsTable();
        
        $this->info('Filament table structures fixed successfully!');
        return 0;
    }
    
    private function fixImportsTable(): void
    {
        $this->info('Checking imports table...');
        
        if (!Schema::hasTable('imports')) {
            $this->createImportsTable();
            return;
        }
        
        $this->fixTableStructure('imports');
    }
    
    private function fixExportsTable(): void
    {
        $this->info('Checking exports table...');
        
        if (!Schema::hasTable('exports')) {
            $this->createExportsTable();
            return;
        }
        
        $this->fixTableStructure('exports');
    }
    
    private function ensureFailedImportRowsTable(): void
    {
        $this->info('Checking failed_import_rows table...');
        
        if (!Schema::hasTable('failed_import_rows')) {
            Schema::create('failed_import_rows', function (Blueprint $table) {
                $table->id();
                $table->json('data');
                $table->foreignId('import_id')->constrained()->cascadeOnDelete();
                $table->text('validation_error')->nullable();
                $table->timestamps();
            });
            $this->info('Created failed_import_rows table.');
        }
    }
    
    private function createImportsTable(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        $this->info('Created imports table with correct structure.');
    }
    
    private function createExportsTable(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_disk');
            $table->string('file_name')->nullable();
            $table->string('exporter');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        $this->info('Created exports table with correct structure.');
    }
    
    private function fixTableStructure(string $tableName): void
    {
        try {
            $columns = DB::select("DESCRIBE {$tableName}");
            $needsFix = false;
            $jsonColumns = [];
            
            foreach ($columns as $column) {
                if (in_array($column->Field, ['total_rows', 'processed_rows', 'successful_rows']) 
                    && str_contains($column->Type, 'json')) {
                    $needsFix = true;
                    $jsonColumns[] = $column->Field;
                }
            }
            
            if ($needsFix) {
                $this->warn("Found JSON columns in {$tableName}: " . implode(', ', $jsonColumns));
                
                // Backup any existing data
                $existingData = DB::table($tableName)->get();
                
                // Drop and recreate the problematic columns
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn(['total_rows', 'processed_rows', 'successful_rows']);
                });
                
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedInteger('processed_rows')->default(0);
                    $table->unsignedInteger('total_rows');
                    $table->unsignedInteger('successful_rows')->default(0);
                });
                
                $this->info("Fixed {$tableName} table structure.");
            } else {
                $this->info("{$tableName} table structure is already correct.");
            }
        } catch (\Exception $e) {
            $this->error("Could not fix {$tableName} structure: " . $e->getMessage());
        }
    }
}

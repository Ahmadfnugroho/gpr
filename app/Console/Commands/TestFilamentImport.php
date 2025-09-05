<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class TestFilamentImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filament:test-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Filament import functionality and database structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Filament Import functionality...');
        
        // Test database structure
        $this->testDatabaseStructure();
        
        // Test creating an import record
        $this->testImportRecordCreation();
        
        return 0;
    }
    
    private function testDatabaseStructure(): void
    {
        $this->info('\nTesting database structure...');
        
        try {
            $columns = DB::select('DESCRIBE imports');
            
            $this->info('Imports table columns:');
            foreach ($columns as $column) {
                $this->line("- {$column->Field}: {$column->Type}");
                
                if (in_array($column->Field, ['total_rows', 'processed_rows', 'successful_rows'])) {
                    if (str_contains($column->Type, 'json')) {
                        $this->error("❌ Column {$column->Field} has JSON type instead of integer!");
                    } else {
                        $this->info("✅ Column {$column->Field} has correct integer type");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Error checking database structure: ' . $e->getMessage());
        }
    }
    
    private function testImportRecordCreation(): void
    {
        $this->info('\nTesting import record creation...');
        
        try {
            $user = User::first();
            if (!$user) {
                $this->error('No users found. Please create a user first.');
                return;
            }
            
            // Test direct database insert
            $testData = [
                'user_id' => $user->id,
                'file_name' => 'test.csv',
                'file_path' => '/tmp/test.csv',
                'importer' => 'App\\Filament\\Imports\\ProductImporter',
                'total_rows' => 100,
                'processed_rows' => 0,
                'successful_rows' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $importId = DB::table('imports')->insertGetId($testData);
            $this->info("✅ Successfully created test import record with ID: {$importId}");
            
            // Clean up test record
            DB::table('imports')->where('id', $importId)->delete();
            $this->info('✅ Test import record cleaned up.');
            
        } catch (\Exception $e) {
            $this->error('Error testing import record creation: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}

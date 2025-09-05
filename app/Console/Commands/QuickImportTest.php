<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;

class QuickImportTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:quick-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quick test to simulate Filament import record creation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Quick Import Test - Simulating Filament Import Process...');
        
        try {
            // Create a user if none exists
            $user = User::first();
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                ]);
                $this->info('Created test user.');
            }
            
            // Simulate the exact data that would be inserted during import
            $importData = [
                'user_id' => $user->id,
                'file_name' => 'import_product non RI.csv',
                'file_path' => '/var/www/gpr/storage/app/private/livewire-tmp/test.csv',
                'importer' => 'App\\Filament\\Imports\\ProductImporter',
                'total_rows' => 597,  // This is the value that's causing issues
                'processed_rows' => 0,
                'successful_rows' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $this->info('📋 Attempting to insert import record...');
            $this->info('Data to insert:');
            foreach ($importData as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
            
            // Try the insert
            $importId = DB::table('imports')->insertGetId($importData);
            
            $this->info("✅ SUCCESS! Created import record with ID: {$importId}");
            
            // Verify the record
            $record = DB::table('imports')->find($importId);
            $this->info('📊 Record verification:');
            $this->line("  ID: {$record->id}");
            $this->line("  Total Rows: {$record->total_rows} (type: " . gettype($record->total_rows) . ")");
            $this->line("  Processed Rows: {$record->processed_rows} (type: " . gettype($record->processed_rows) . ")");
            $this->line("  Successful Rows: {$record->successful_rows} (type: " . gettype($record->successful_rows) . ")");
            
            // Clean up
            DB::table('imports')->where('id', $importId)->delete();
            $this->info('🧹 Cleaned up test record.');
            
        } catch (\Exception $e) {
            $this->error('❌ FAILED: ' . $e->getMessage());
            $this->error('💡 This indicates the table structure still has JSON columns instead of integers.');
            
            // Show the exact query that failed
            $this->error('🔍 Error details:');
            $this->error('File: ' . $e->getFile());
            $this->error('Line: ' . $e->getLine());
            $this->error('Trace: ' . $e->getTraceAsString());
            
            return 1;
        }
        
        return 0;
    }
}

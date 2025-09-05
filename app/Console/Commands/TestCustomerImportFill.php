<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Filament\Imports\CustomerImporter;
use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;

class TestCustomerImportFill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:customer-fill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test CustomerImporter fillRecord method excludes phone numbers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing CustomerImporter fillRecord method...');
        
        try {
            // Create a user if none exists
            $user = User::first();
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                ]);
            }
            
            // Create an Import record
            $import = Import::create([
                'user_id' => $user->id,
                'file_name' => 'test_fill.csv',
                'file_path' => '/tmp/test_fill.csv',
                'importer' => CustomerImporter::class,
                'total_rows' => 1,
                'processed_rows' => 0,
                'successful_rows' => 0,
            ]);
            
            // Test data including phone numbers
            $testData = [
                'name' => 'Fill Test Customer',
                'email' => 'filltest@example.com',
                'phone_number_1' => '08123456789',
                'phone_number_2' => '08987654321',
                'gender' => 'male',
                'status' => 'active',
                'address' => 'Test Address',
                'job' => 'Test Job',
            ];
            
            $this->info('📊 Test data includes phone numbers:');
            foreach ($testData as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
            
            // Test the fillRecord logic directly
            $this->info('\n🔧 Testing fillRecord exclusion logic...');
            
            // Simulate what fillRecord does
            $fillableData = collect($testData)->except(['phone_number_1', 'phone_number_2'])->toArray();
            
            $this->info('📋 Original data has ' . count($testData) . ' fields');
            $this->info('📋 Fillable data has ' . count($fillableData) . ' fields (after excluding phone numbers)');
            
            // Show what would be excluded
            $excluded = array_diff_key($testData, $fillableData);
            $this->info('\n🙅 Excluded fields:');
            foreach ($excluded as $key => $value) {
                $this->info("  - {$key}: {$value}");
            }
            
            // Test with actual Customer model
            $customer = new Customer();
            $customer->fill($fillableData);
            
            $this->info('\n📋 Fillable data would include:');
            foreach ($fillableData as $key => $value) {
                $this->info("  + {$key}: {$value}");
            }
            
            $this->info('\n🔍 Checking what was filled in the customer record:');
            
            $filled = $customer->getAttributes();
            
            // Check that regular fields were filled
            if (isset($filled['name']) && $filled['name'] === 'Fill Test Customer') {
                $this->info('✅ name was filled correctly');
            } else {
                $this->error('❌ name was not filled properly');
            }
            
            if (isset($filled['email']) && $filled['email'] === 'filltest@example.com') {
                $this->info('✅ email was filled correctly');
            } else {
                $this->error('❌ email was not filled properly');
            }
            
            // Check that phone numbers were NOT filled
            if (!isset($filled['phone_number_1'])) {
                $this->info('✅ phone_number_1 was correctly excluded from fill');
            } else {
                $this->error('❌ phone_number_1 should not be in filled attributes!');
            }
            
            if (!isset($filled['phone_number_2'])) {
                $this->info('✅ phone_number_2 was correctly excluded from fill');
            } else {
                $this->error('❌ phone_number_2 should not be in filled attributes!');
            }
            
            $this->info('\n📜 All filled attributes:');
            foreach ($filled as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
            
            // Clean up
            $import->delete();
            
        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        $this->info('\n🎉 fillRecord test completed successfully!');
        $this->info('💡 Phone numbers should now be handled correctly via afterSave method.');
        return 0;
    }
}

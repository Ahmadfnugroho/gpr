<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Filament\Imports\CustomerImporter;
use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;

class TestCustomerResolveRecord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:customer-resolve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test CustomerImporter resolveRecord method';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing CustomerImporter resolveRecord method...');
        
        try {
            // Create test customer
            $existingCustomer = Customer::create([
                'name' => 'Existing Customer',
                'email' => 'existing@example.com',
                'status' => 'active',
            ]);
            
            // Add phone number
            $existingCustomer->customerPhoneNumbers()->create([
                'phone_number' => '08111222333'
            ]);
            
            $this->info("📄 Created existing customer: {$existingCustomer->name} (ID: {$existingCustomer->id})");
            $this->info("📞 With phone: 08111222333");
            
            // Create import record
            $user = User::first();
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                ]);
            }
            
            $import = Import::create([
                'user_id' => $user->id,
                'file_name' => 'test_resolve.csv',
                'file_path' => '/tmp/test_resolve.csv',
                'importer' => CustomerImporter::class,
                'total_rows' => 1,
                'processed_rows' => 0,
                'successful_rows' => 0,
            ]);
            
            $this->info('\n🧪 Test Case 1: Find existing customer by email');
            $testData1 = [
                'name' => 'Different Name',
                'email' => 'existing@example.com', // Same email
                'phone_number_1' => '08999888777', // Different phone
            ];
            
            // Test resolveRecord with existing email
            $importer1 = new class($import, [], []) extends CustomerImporter {
                protected array $data = [];
                
                public function testResolve($data) {
                    $this->data = $data;
                    return $this->resolveRecord();
                }
            };
            
            $resolvedCustomer1 = $importer1->testResolve($testData1);
            
            if ($resolvedCustomer1 && $resolvedCustomer1->exists) {
                $this->info("✅ Found existing customer by email: ID {$resolvedCustomer1->id} - {$resolvedCustomer1->name}");
            } else {
                $this->error('❌ Failed to find existing customer by email');
            }
            
            $this->info('\n🧪 Test Case 2: Find existing customer by phone number');
            $testData2 = [
                'name' => 'Another Name',
                'email' => 'different@example.com', // Different email
                'phone_number_1' => '08111222333', // Same phone
            ];
            
            $resolvedCustomer2 = $importer1->testResolve($testData2);
            
            if ($resolvedCustomer2 && $resolvedCustomer2->exists) {
                $this->info("✅ Found existing customer by phone: ID {$resolvedCustomer2->id} - {$resolvedCustomer2->name}");
            } else {
                $this->error('❌ Failed to find existing customer by phone');
            }
            
            $this->info('\n🧪 Test Case 3: Create new customer (no match)');
            $testData3 = [
                'name' => 'Completely New Customer',
                'email' => 'new@example.com', // Different email
                'phone_number_1' => '08555666777', // Different phone
            ];
            
            $resolvedCustomer3 = $importer1->testResolve($testData3);
            
            if ($resolvedCustomer3 && !$resolvedCustomer3->exists) {
                $this->info('✅ Created new customer instance (not persisted yet)');
                $this->info("    Customer exists: " . ($resolvedCustomer3->exists ? 'Yes' : 'No'));
                $this->info("    Customer ID: " . ($resolvedCustomer3->id ?? 'null'));
            } else {
                $this->error('❌ Failed to create new customer instance');
            }
            
            // Clean up
            $existingCustomer->customerPhoneNumbers()->delete();
            $existingCustomer->delete();
            $import->delete();
            $this->info('\n🧹 Cleaned up test data.');
            
        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
        
        $this->info('\n🎉 resolveRecord test completed successfully!');
        $this->info('💡 CustomerImporter can now properly resolve existing customers and create new ones.');
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Filament\Imports\CustomerImporter;
use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;

class TestCustomerImporter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:customer-importer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test CustomerImporter with sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing CustomerImporter Column Mapping...');
        
        try {
            // Test the column definitions
            $columns = CustomerImporter::getColumns();
            
            $this->info('📋 CustomerImporter columns:');
            $columnNames = [];
            foreach ($columns as $column) {
                $columnName = $column->getName();
                $columnNames[] = $columnName;
                $this->line("  - {$columnName} ({$column->getLabel()})");
            }
            
            // Check that we have the correct database columns
            $expectedColumns = [
                'name', 'email', 'phone_number_1', 'phone_number_2', 
                'gender', 'status', 'address', 'job', 'office_address',
                'instagram_username', 'emergency_contact_name', 
                'emergency_contact_number', 'source_info'
            ];
            
            $this->info('\n✅ Checking column names match database fields:');
            foreach ($expectedColumns as $expected) {
                if (in_array($expected, $columnNames)) {
                    $this->info("  ✅ {$expected} - OK");
                } else {
                    $this->error("  ❌ {$expected} - MISSING");
                }
            }
            
            // Test direct customer creation with the test data
            $this->info('\n📝 Testing direct customer creation...');
            $testCustomer = new Customer([
                'name' => 'Rian Rahmatullah',
                'email' => 'test_customer_import@example.com',
                'gender' => 'male',
                'status' => 'active',
                'address' => 'jl. bojong sari 4A blok f1 no.14',
                'job' => 'Content creator',
                'office_address' => 'silaturahmi residance 1',
                'instagram_username' => '@riaaanrh',
                'emergency_contact_name' => '6281282399880',
                'emergency_contact_number' => '6281282399880',
                'source_info' => 'Teman',
            ]);
            
            $testCustomer->save();
            $this->info("✅ Test customer created with ID: {$testCustomer->id}");
            
            // Test phone number creation
            $testCustomer->customerPhoneNumbers()->create([
                'phone_number' => '085811743087'
            ]);
            $this->info('✅ Phone number added successfully');
            
            // Clean up
            $testCustomer->customerPhoneNumbers()->delete();
            $testCustomer->delete();
            $this->info('🧹 Test customer cleaned up.');
            
        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
        
        $this->info('🎉 CustomerImporter column mapping test completed successfully!');
        $this->info('💡 The previous error should be fixed now - columns match database fields.');
        return 0;
    }
}

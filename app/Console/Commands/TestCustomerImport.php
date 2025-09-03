<?php

namespace App\Console\Commands;

use App\Services\CustomerImportExportService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TestCustomerImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:test-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test customer import functionality';

    protected $importExportService;

    public function __construct(CustomerImportExportService $importExportService)
    {
        parent::__construct();
        $this->importExportService = $importExportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Customer Import/Export functionality...');

        // Test template generation
        $this->info('1. Testing template generation...');
        try {
            $templatePath = $this->importExportService->generateTemplate();
            if (file_exists($templatePath)) {
                $this->info("✓ Template generated successfully: {$templatePath}");
            } else {
                $this->error("✗ Template generation failed");
                return;
            }
        } catch (\Exception $e) {
            $this->error("✗ Template generation error: " . $e->getMessage());
            return;
        }

        // Test export functionality
        $this->info('2. Testing export functionality...');
        try {
            $exportPath = $this->importExportService->exportCustomers();
            if (file_exists($exportPath)) {
                $this->info("✓ Export generated successfully: {$exportPath}");
                
                // Clean up
                unlink($exportPath);
                $this->info("✓ Export file cleaned up");
            } else {
                $this->error("✗ Export generation failed");
            }
        } catch (\Exception $e) {
            $this->error("✗ Export error: " . $e->getMessage());
        }

        // Create sample data for import test
        $this->info('3. Creating sample import data...');
        $sampleData = $this->createSampleImportFile();
        
        if ($sampleData) {
            $this->info("✓ Sample data created: {$sampleData}");
            
            // Test file validation
            $this->info('4. Testing file validation...');
            try {
                $uploadedFile = new UploadedFile(
                    $sampleData,
                    'sample_customers.csv',
                    'text/csv',
                    null,
                    true
                );
                
                $validation = $this->importExportService->validateFileStructure($uploadedFile);
                
                if ($validation['valid']) {
                    $this->info("✓ File validation passed");
                    $this->info("   Total rows: " . $validation['total_rows']);
                } else {
                    $this->error("✗ File validation failed: " . implode(', ', $validation['errors']));
                }
                
                // Test import
                $this->info('5. Testing import functionality...');
                $results = $this->importExportService->importCustomers($uploadedFile);
                
                $this->info("✓ Import completed:");
                $this->line("   Total processed: " . $results['total']);
                $this->line("   Successfully imported: " . $results['success']);
                $this->line("   Updated: " . $results['updated']);
                $this->line("   Failed: " . $results['failed']);
                
                if (!empty($results['errors'])) {
                    $this->warn("   Errors encountered:");
                    foreach ($results['errors'] as $error) {
                        $this->line("   - {$error}");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("✗ Import test error: " . $e->getMessage());
            }
            
            // Clean up sample file
            unlink($sampleData);
            $this->info("✓ Sample file cleaned up");
        }

        // Clean up template file
        if (file_exists($templatePath)) {
            unlink($templatePath);
            $this->info("✓ Template file cleaned up");
        }

        $this->info('✅ Customer Import/Export test completed!');
    }

    /**
     * Create sample import file for testing
     */
    private function createSampleImportFile(): ?string
    {
        $data = [
            ['nama_lengkap', 'email', 'nomor_hp_1', 'nomor_hp_2', 'jenis_kelamin', 'status', 'alamat', 'pekerjaan', 'alamat_kantor', 'instagram', 'facebook', 'kontak_emergency', 'hp_emergency', 'sumber_info'],
            ['John Doe', 'john.doe@example.com', '081234567890', '087654321098', 'male', 'active', 'Jl. Contoh No. 123, Jakarta', 'Software Developer', 'Jl. Kantor No. 456, Jakarta', 'johndoe', 'john.doe', 'Jane Doe', '081987654321', 'Website'],
            ['Jane Smith', 'jane.smith@example.com', '082345678901', '', 'female', 'active', 'Jl. Sample No. 456, Bandung', 'Designer', 'Jl. Office No. 789, Bandung', 'janesmith', 'jane.smith', 'John Smith', '082876543210', 'Instagram'],
            ['Bob Johnson', 'bob.johnson@example.com', '083456789012', '088765432109', 'male', 'inactive', 'Jl. Test No. 789, Surabaya', 'Manager', 'Jl. Work No. 012, Surabaya', 'bobjohnson', 'bob.johnson', 'Alice Johnson', '083765432109', 'Referral']
        ];

        $filename = storage_path('app/sample_customers_import.csv');
        
        try {
            $handle = fopen($filename, 'w');
            
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            
            fclose($handle);
            
            return $filename;
            
        } catch (\Exception $e) {
            $this->error("Error creating sample file: " . $e->getMessage());
            return null;
        }
    }
}

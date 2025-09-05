<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerPhoneNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use Exception;

class CustomerImporter implements
    ToCollection,
    WithHeadingRow,
    WithBatchInserts,
    WithChunkReading,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $importResults = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'updated' => 0,
        'errors' => []
    ];

    protected $updateExisting = false;
    protected $defaultPasswordHash;
    
    // Pre-computed hash for 'password123' to avoid slow bcrypt operations
    // Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

    public function __construct($updateExisting = false)
    {
        $this->updateExisting = $updateExisting;
        // Pre-computed hash for 'password123' - avoids slow bcrypt during import
        $this->defaultPasswordHash = '$2y$12$qEqBlUveXdsHy60sk6MoWeLnuh12UyGL89C.BXcq3bPtitVMTNxAm';
    }

    /**
     * Process the collection of imported data with bulk operations
     */
    public function collection(Collection $rows): void
    {
        $this->importResults['total'] = $rows->count();
        
        // Process in smaller chunks to avoid memory issues
        $rows->chunk(25)->each(function ($chunk, $chunkIndex) {
            $this->processBulkChunk($chunk, $chunkIndex);
            
            // Force garbage collection after each chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });
    }

    /**
     * Process a chunk of rows with bulk operations
     */
    protected function processBulkChunk(Collection $chunk, int $chunkIndex): void
    {
        $validRows = [];
        $customersToCreate = [];
        $phoneNumbersToCreate = [];
        $existingEmails = [];
        
        // First pass: Validate and normalize all rows
        foreach ($chunk as $index => $row) {
            $globalRowNumber = ($chunkIndex * 50) + $index + 2; // Global row number
            
            try {
                $customerData = $this->normalizeRowData($row->toArray());
                $validator = $this->validateRowData($customerData, $globalRowNumber);
                
                if ($validator->fails()) {
                    $this->importResults['failed']++;
                    foreach ($validator->errors()->all() as $error) {
                        $this->importResults['errors'][] = "Baris {$globalRowNumber}: {$error}";
                    }
                    continue;
                }
                
                $validRows[] = [
                    'data' => $customerData,
                    'row_number' => $globalRowNumber
                ];
                
            } catch (Exception $e) {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$globalRowNumber}: {$e->getMessage()}";
                Log::error("Import error on row {$globalRowNumber}: " . $e->getMessage());
            }
        }
        
        if (empty($validRows)) {
            return; // No valid rows in this chunk
        }
        
        // Second pass: Check for existing customers in bulk
        $emails = collect($validRows)->pluck('data.email')->toArray();
        $existingCustomers = Customer::whereIn('email', $emails)
            ->get()
            ->keyBy('email');
        
        // Third pass: Prepare bulk insert data
        foreach ($validRows as $validRow) {
            $customerData = $validRow['data'];
            $rowNumber = $validRow['row_number'];
            $email = $customerData['email'];
            
            if ($existingCustomers->has($email)) {
                if ($this->updateExisting) {
                    $this->updateCustomer($existingCustomers->get($email), $customerData, $rowNumber);
                } else {
                    $this->importResults['failed']++;
                    $this->importResults['errors'][] = "Baris {$rowNumber}: Email '{$email}' sudah terdaftar";
                }
                continue;
            }
            
            // Prepare customer data for bulk insert
            $customerData['status'] = $customerData['status'] ?: Customer::STATUS_BLACKLIST;
            $customerData['password'] = $this->defaultPasswordHash; // Use pre-computed hash for speed
            $customerData['created_at'] = now();
            $customerData['updated_at'] = now();
            
            // Remove phone data from customer data
            $phone1 = $customerData['phone1'] ?? null;
            $phone2 = $customerData['phone2'] ?? null;
            unset($customerData['phone1'], $customerData['phone2']);
            
            $customersToCreate[] = [
                'data' => $customerData,
                'phone1' => $phone1,
                'phone2' => $phone2,
                'row_number' => $rowNumber
            ];
        }
        
        // Bulk insert customers
        if (!empty($customersToCreate)) {
            $this->bulkCreateCustomers($customersToCreate);
        }
    }

    /**
     * Process individual row
     */
    protected function processRow(array $row, int $rowNumber): void
    {
        // Normalize and validate row data
        $customerData = $this->normalizeRowData($row);

        // Validate the data
        $validator = $this->validateRowData($customerData, $rowNumber);

        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Check if customer exists
        $existingCustomer = Customer::where('email', $customerData['email'])->first();

        if ($existingCustomer) {
            if ($this->updateExisting) {
                $this->updateCustomer($existingCustomer, $customerData, $rowNumber);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Email '{$customerData['email']}' sudah terdaftar";
                return;
            }
        } else {
            $this->createCustomer($customerData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'name' => trim($row['nama_lengkap'] ?? $row['name'] ?? ''),
            'email' => strtolower(trim($row['email'] ?? '')),
            'phone1' => $this->formatPhoneNumber($row['nomor_hp_1'] ?? $row['phone1'] ?? ''),
            'phone2' => $this->formatPhoneNumber($row['nomor_hp_2'] ?? $row['phone2'] ?? ''),
            'gender' => $this->normalizeGender($row['jenis_kelamin'] ?? $row['gender'] ?? ''),
            'status' => $this->normalizeStatus($row['status'] ?? ''),
            'address' => trim($row['alamat'] ?? $row['address'] ?? ''),
            'job' => trim($row['pekerjaan'] ?? $row['job'] ?? ''),
            'office_address' => trim($row['alamat_kantor'] ?? $row['office_address'] ?? ''),
            'instagram_username' => $this->formatInstagram($row['instagram'] ?? $row['instagram_username'] ?? ''),
            'emergency_contact_name' => trim($row['kontak_emergency'] ?? $row['emergency_contact_name'] ?? ''),
            'emergency_contact_number' => $this->formatPhoneNumber($row['hp_emergency'] ?? $row['emergency_contact_number'] ?? ''),
            'source_info' => trim($row['sumber_info'] ?? $row['source_info'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone1' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'status' => 'nullable|in:active,inactive,blacklist',
            'address' => 'nullable|string',
            'job' => 'nullable|string|max:255',
            'office_address' => 'nullable|string',
            'instagram_username' => 'nullable|string|max:255',
            'facebook_username' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:20',
            'source_info' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Nama lengkap wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'gender.in' => 'Jenis kelamin harus: male atau female',
            'status.in' => 'Status harus: active, inactive, atau blacklist',
        ]);
    }

    /**
     * Create new customer
     */
    protected function createCustomer(array $data, int $rowNumber): void
    {
        // Set default values
        $data['status'] = $data['status'] ?: Customer::STATUS_BLACKLIST;
        $data['password'] = $this->defaultPasswordHash; // Use pre-computed hash for speed

        // Create customer
        $customer = Customer::create($data);

        // Add phone numbers
        $this->addPhoneNumbers($customer, $data);

        $this->importResults['success']++;
        Log::info("Customer imported successfully", [
            'row' => $rowNumber,
            'customer_id' => $customer->id,
            'email' => $customer->email
        ]);
    }

    /**
     * Update existing customer
     */
    protected function updateCustomer(Customer $customer, array $data, int $rowNumber): void
    {
        // Don't update email and password
        unset($data['email'], $data['password']);

        // Update customer data
        $customer->update($data);

        // Update phone numbers
        $this->updatePhoneNumbers($customer, $data);

        $this->importResults['updated']++;
        Log::info("Customer updated successfully", [
            'row' => $rowNumber,
            'customer_id' => $customer->id,
            'email' => $customer->email
        ]);
    }

    /**
     * Add phone numbers to customer
     */
    protected function addPhoneNumbers(Customer $customer, array $data): void
    {
        if (!empty($data['phone1'])) {
            CustomerPhoneNumber::create([
                'customer_id' => $customer->id,
                'phone_number' => $data['phone1']
            ]);
        }

        if (!empty($data['phone2']) && $data['phone2'] !== $data['phone1']) {
            CustomerPhoneNumber::create([
                'customer_id' => $customer->id,
                'phone_number' => $data['phone2']
            ]);
        }
    }

    /**
     * Update phone numbers for existing customer
     */
    protected function updatePhoneNumbers(Customer $customer, array $data): void
    {
        // Delete existing phone numbers
        $customer->customerPhoneNumbers()->delete();

        // Add new phone numbers
        $this->addPhoneNumbers($customer, $data);
    }

    /**
     * Bulk create customers and their phone numbers
     */
    protected function bulkCreateCustomers(array $customersToCreate): void
    {
        DB::beginTransaction();
        
        try {
            // Prepare customer data for bulk insert
            $customerInsertData = [];
            $phoneNumbersData = [];
            
            foreach ($customersToCreate as $customerInfo) {
                $customerInsertData[] = $customerInfo['data'];
            }
            
            // Bulk insert customers
            Customer::insert($customerInsertData);
            
            // Get the newly created customers with their IDs
            $emails = collect($customerInsertData)->pluck('email')->toArray();
            $newCustomers = Customer::whereIn('email', $emails)
                ->get()
                ->keyBy('email');
            
            // Prepare phone numbers for bulk insert
            foreach ($customersToCreate as $customerInfo) {
                $email = $customerInfo['data']['email'];
                $phone1 = $customerInfo['phone1'];
                $phone2 = $customerInfo['phone2'];
                $rowNumber = $customerInfo['row_number'];
                
                if ($newCustomers->has($email)) {
                    $customerId = $newCustomers->get($email)->id;
                    
                    if (!empty($phone1)) {
                        $phoneNumbersData[] = [
                            'customer_id' => $customerId,
                            'phone_number' => $phone1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    
                    if (!empty($phone2) && $phone2 !== $phone1) {
                        $phoneNumbersData[] = [
                            'customer_id' => $customerId,
                            'phone_number' => $phone2,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    
                    $this->importResults['success']++;
                    
                } else {
                    $this->importResults['failed']++;
                    $this->importResults['errors'][] = "Baris {$rowNumber}: Gagal membuat customer dengan email {$email}";
                }
            }
            
            // Bulk insert phone numbers if any
            if (!empty($phoneNumbersData)) {
                CustomerPhoneNumber::insert($phoneNumbersData);
            }
            
            DB::commit();
            
            Log::info('Bulk customer import completed', [
                'customers_created' => count($customersToCreate),
                'phone_numbers_created' => count($phoneNumbersData)
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            // Mark all customers in this batch as failed
            foreach ($customersToCreate as $customerInfo) {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$customerInfo['row_number']}: Gagal bulk insert - {$e->getMessage()}";
            }
            
            Log::error('Bulk customer import failed', [
                'error' => $e->getMessage(),
                'customers_count' => count($customersToCreate)
            ]);
        }
    }

    /**
     * Format phone number to Indonesian format
     */
    protected function formatPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) return null;

        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '62')) {
            return '+' . $phone;
        } elseif (str_starts_with($phone, '0')) {
            return '+62' . substr($phone, 1);
        } elseif (!empty($phone)) {
            return '+62' . $phone;
        }

        return null;
    }

    /**
     * Normalize gender value
     */
    protected function normalizeGender(?string $gender): ?string
    {
        if (empty($gender)) return null;

        $gender = strtolower(trim($gender));

        if (in_array($gender, ['laki-laki', 'laki', 'l', 'male', 'm', 'pria'])) {
            return 'male';
        } elseif (in_array($gender, ['perempuan', 'wanita', 'p', 'female', 'f', 'cewek'])) {
            return 'female';
        }

        return null;
    }

    /**
     * Normalize status value
     */
    protected function normalizeStatus(?string $status): ?string
    {
        if (empty($status)) return null;

        $status = strtolower(trim($status));

        if (in_array($status, ['aktif', 'active', 'a'])) {
            return 'active';
        } elseif (in_array($status, ['tidak aktif', 'inactive', 'nonaktif', 'i'])) {
            return 'inactive';
        } elseif (in_array($status, ['blacklist', 'hitam', 'banned', 'b'])) {
            return 'blacklist';
        }

        return null;
    }

    /**
     * Format Instagram username
     */
    protected function formatInstagram(?string $instagram): ?string
    {
        if (empty($instagram)) return null;

        $instagram = trim($instagram);

        // Remove @ if present
        if (str_starts_with($instagram, '@')) {
            $instagram = substr($instagram, 1);
        }

        return $instagram;
    }

    /**
     * Get import results
     */
    public function getImportResults(): array
    {
        return $this->importResults;
    }

    /**
     * Batch insert size
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Chunk reading size
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Get expected headers for template
     */
    public static function getExpectedHeaders(): array
    {
        return [
            'nama_lengkap',
            'email',
            'nomor_hp_1',
            'nomor_hp_2',
            'jenis_kelamin',
            'status',
            'alamat',
            'pekerjaan',
            'alamat_kantor',
            'instagram',
            'kontak_emergency',
            'hp_emergency',
            'sumber_info'
        ];
    }
}

<?php

namespace App\Imports;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;
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

class UserImporter implements 
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

    public function __construct($updateExisting = false)
    {
        $this->updateExisting = $updateExisting;
    }

    /**
     * Process the collection of imported data
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->importResults['total']++;
            $rowNumber = $index + 2; // +2 because index starts from 0 and there's a header

            try {
                $this->processRow($row->toArray(), $rowNumber);
            } catch (Exception $e) {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$e->getMessage()}";
                Log::error("Import error on row {$rowNumber}: " . $e->getMessage(), [
                    'row_data' => $row->toArray()
                ]);
            }
        }
    }

    /**
     * Process individual row
     */
    protected function processRow(array $row, int $rowNumber): void
    {
        // Normalize and validate row data
        $userData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($userData, $rowNumber);
        
        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Check if user exists (by email since it should be unique)
        $existingUser = User::where('email', $userData['email'])->first();
        
        if ($existingUser) {
            if ($this->updateExisting) {
                $this->updateUser($existingUser, $userData, $rowNumber);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: User '{$userData['email']}' sudah ada";
                return;
            }
        } else {
            $this->createUser($userData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'name' => trim($row['nama'] ?? $row['name'] ?? ''),
            'email' => strtolower(trim($row['email'] ?? '')),
            'password' => $row['password'] ?? 'password123',
            'role' => $this->normalizeRole($row['role'] ?? 'panel_user'),
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
            'password' => 'required|string|min:6',
            'role' => 'required|string',
        ], [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
            'role.required' => 'Role wajib diisi',
        ]);
    }

    /**
     * Create new user
     */
    protected function createUser(array $data, int $rowNumber): void
    {
        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(), // Auto-verify imported users
        ]);
        
        // Assign role
        $this->assignRole($user, $data['role']);
        
        $this->importResults['success']++;
        Log::info("User imported successfully", [
            'row' => $rowNumber,
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    /**
     * Update existing user
     */
    protected function updateUser(User $user, array $data, int $rowNumber): void
    {
        // Update user data (but don't update password unless specified)
        $updateData = [
            'name' => $data['name'],
        ];
        
        // Only update password if it's not the default
        if ($data['password'] !== 'password123') {
            $updateData['password'] = Hash::make($data['password']);
        }
        
        $user->update($updateData);
        
        // Update role
        $this->assignRole($user, $data['role']);
        
        $this->importResults['updated']++;
        Log::info("User updated successfully", [
            'row' => $rowNumber,
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    /**
     * Assign role to user
     */
    protected function assignRole(User $user, string $roleName): void
    {
        // Remove all existing roles
        $user->syncRoles([]);
        
        // Get or create role
        $role = Role::firstOrCreate(['name' => $roleName]);
        
        // Assign role to user
        $user->assignRole($role);
    }

    /**
     * Normalize role value
     */
    protected function normalizeRole(?string $role): string
    {
        if (empty($role)) return 'panel_user';
        
        $role = strtolower(trim($role));
        
        $roleMap = [
            'superadmin' => 'super_admin',
            'super_admin' => 'super_admin',
            'admin' => 'admin',
            'administrator' => 'admin',
            'staff' => 'staff',
            'employee' => 'staff',
            'user' => 'panel_user',
            'panel_user' => 'panel_user',
        ];
        
        return $roleMap[$role] ?? 'panel_user';
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
            'nama',
            'email',
            'password',
            'role'
        ];
    }
}

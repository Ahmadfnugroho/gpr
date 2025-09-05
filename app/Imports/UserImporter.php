<?php

namespace App\Imports;

use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Traits\EnhancedImporterTrait;
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
    use Importable, SkipsErrors, SkipsFailures, EnhancedImporterTrait;

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
            $this->incrementTotal();
            $rowNumber = $index + 2;
            $rowArray = $row->toArray();

            if ($this->shouldSkipRow($rowArray)) {
                continue;
            }

            try {
                $this->processRow($rowArray, $rowNumber);
            } catch (Exception $e) {
                $this->incrementFailed();
                $errorMessage = $e->getMessage();
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($rowArray, $rowNumber, $errorMessage);
                $this->logImportError($errorMessage, $rowNumber, $rowArray);
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
            $this->incrementFailed();
            $errorMessages = [];
            foreach ($validator->errors()->all() as $error) {
                $this->addError("Baris {$rowNumber}: {$error}");
                $errorMessages[] = $error;
            }
            $this->addFailedRow($row, $rowNumber, implode(' | ', $errorMessages));
            return;
        }

        // Check if user exists (by email since it should be unique)
        $existingUser = User::where('email', $userData['email'])->first();
        
        if ($existingUser) {
            if ($this->updateExisting) {
                $this->updateUser($existingUser, $userData, $rowNumber);
            } else {
                $this->incrementFailed();
                $errorMessage = "User '{$userData['email']}' sudah ada";
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($row, $rowNumber, $errorMessage);
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
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);
            
            $this->assignRole($user, $data['role']);
            
            $this->incrementSuccess();
            $this->addMessage("Baris {$rowNumber}: Berhasil menambahkan user '{$user->name}'");
            Log::info("User imported successfully", [
                'row' => $rowNumber,
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal menambahkan user - {$e->getMessage()}");
        }
    }

    /**
     * Update existing user
     */
    protected function updateUser(User $user, array $data, int $rowNumber): void
    {
        try {
            $updateData = ['name' => $data['name']];
            
            if ($data['password'] !== 'password123') {
                $updateData['password'] = Hash::make($data['password']);
            }
            
            $user->update($updateData);
            $this->assignRole($user, $data['role']);
            
            $this->incrementUpdated();
            $this->addMessage("Baris {$rowNumber}: Berhasil mengupdate user '{$user->name}'");
            Log::info("User updated successfully", [
                'row' => $rowNumber,
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal mengupdate user - {$e->getMessage()}");
        }
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

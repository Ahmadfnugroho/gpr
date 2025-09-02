<?php

namespace App\Http\Controllers;

use App\Http\Services\GoogleSheetsServices;
use App\Models\SyncLog;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerPhoneNumber;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class GoogleSheetSyncController
{
    /**
     * Helper function to get column value with flexible naming
     */
    private function getColumnValue($rowData, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            if (isset($rowData[$name]) && !empty($rowData[$name])) {
                return $rowData[$name];
            }
        }
        return null;
    }

    /**
     * Convert gender from Sheet format to database enum
     */
    private function convertGenderToDb($genderValue)
    {
        if (empty($genderValue)) return null;

        $gender = strtolower(trim($genderValue));
        if (in_array($gender, ['laki laki', 'laki-laki', 'male'])) {
            return 'male';
        } elseif (in_array($gender, ['perempuan', 'female'])) {
            return 'female';
        }
        return null;
    }

    /**
     * Convert gender from database to Sheet format
     */
    private function convertGenderToSheet($genderValue)
    {
        if ($genderValue === 'male') return 'Laki Laki';
        if ($genderValue === 'female') return 'Perempuan';
        return '';
    }

    /**
     * Convert status from Sheet format to database enum
     */
    private function convertStatusToDb($statusValue)
    {
        if (empty($statusValue)) return 'blacklist';

        $status = strtolower(trim($statusValue));
        if (in_array($status, ['active', 'aktif'])) {
            return 'active';
        } elseif (in_array($status, ['inactive', 'nonaktif', 'blacklist', 'banned'])) {
            return 'blacklist';
        }
        return 'blacklist'; // default fallback
    }

    /**
     * Convert status from database to Sheet format
     */
    private function convertStatusToSheet($statusValue)
    {
        if (empty($statusValue)) {
            return 'blacklist';
        }

        if ($statusValue === 'active') return 'active';
        if ($statusValue === 'blacklist') return 'blacklist';

        return 'blacklist';
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function sync(Request $request)
    {
        // ✅ Increase execution time limit for large datasets
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');

        try {
            $data = $request->json()->all();

            if (!isset($data['values']) || count($data['values']) < 2) {
                return response()->json(['error' => 'No data found'], 400);
            }

            $headers = array_map(fn($header) => trim($header), $data['values'][0]);
            $rows = array_slice($data['values'], 1);


            DB::transaction(function () use ($headers, $rows) {
                foreach ($rows as $row) {
                    $rowData = array_combine($headers, array_pad($row, count($headers), null));

                    // Pastikan email tersedia sebelum melanjutkan
                    if (empty($rowData['Email Address'])) {
                        continue;
                    }
                    $incomingUpdatedAt = Carbon::parse($rowData['updated_at'] ?? now());

                    // Check if this should be a Customer (rental data) instead of User (admin)
                    $customer = Customer::where('email', $rowData['Email Address'])->first();

                    if ($customer && $customer->updated_at->gte($incomingUpdatedAt)) {
                        continue; // Data lokal lebih baru, lewati
                    }

                    // Use flexible column mapping for better compatibility
                    $gender = $this->convertGenderToDb($this->getColumnValue($rowData, ['Jenis Kelamin']));
                    $statusRaw = $this->getColumnValue($rowData, ['Status', 'STATUS', 'status']) ?? 'blacklist';
                    $status = $this->convertStatusToDb($statusRaw);

                    // Create/Update Customer (not User) since this contains rental customer data
                    $customer = Customer::updateOrCreate(
                        ['email' => $rowData['Email Address']],
                        [
                            'name' => $rowData['Nama Lengkap (Sesuai KTP)'] ?? null,
                            'address' => $rowData['Alamat Tinggal Sekarang (Ditulis Lengkap)'] ?? null,
                            'job' => $rowData['Pekerjaan'] ?? null,
                            'office_address' => $rowData['Alamat Kantor'] ?? null,
                            'instagram_username' => $rowData['Nama akun Instagram penyewa'] ?? null,
                            'emergency_contact_name' => $rowData['Nama Kontak Emergency'] ?? null,
                            'emergency_contact_number' => $rowData['No. Hp Kontak Emergency'] ?? null,
                            'gender' => $gender,
                            'source_info' => $rowData['Mengetahui Global Photo Rental dari'] ?? null,
                            'status' => $status,
                            // Note: Customer has password for authentication, but sync doesn't update it
                        ]
                    );

                    // Simpan nomor telepon ke Customer
                    $phoneNumbers = array_filter([$rowData['No. Hp1'] ?? null, $rowData['No. Hp2'] ?? null]);
                    foreach ($phoneNumbers as $phone) {
                        CustomerPhoneNumber::updateOrCreate(
                            ['customer_id' => $customer->id, 'phone_number' => $phone],
                            ['customer_id' => $customer->id, 'phone_number' => $phone]
                        );
                    }
                }
            });

            return response()->json(['message' => 'Data synchronized successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        $lastSyncAt = $request->query('since');
        // Export Customer data (not User data) since this is for rental customers
        $customers = Customer::with('customerPhoneNumbers')
            ->when($lastSyncAt, fn($q) => $q->where('updated_at', '>', $lastSyncAt))
            ->get();

        $headers = [
            'Email Address',
            'Nama Lengkap (Sesuai KTP)',
            'Alamat Tinggal Sekarang (Ditulis Lengkap)',
            'Pekerjaan',
            'Alamat Kantor',
            'Nama akun Instagram penyewa',
            'Nama Kontak Emergency',
            'No. Hp Kontak Emergency',
            'Jenis Kelamin',
            'Mengetahui Global Photo Rental dari',
            'Status',
            'No. Hp1',
            'No. Hp2',
            'updated_at',
        ];

        $values = [];
        $values[] = $headers;

        foreach ($customers as $customer) {
            $phones = $customer->customerPhoneNumbers->pluck('phone_number')->values();

            $values[] = [
                $customer->email,
                $customer->name,
                $customer->address,
                $customer->job,
                $customer->office_address,
                $customer->instagram_username,
                $customer->emergency_contact_name,
                $customer->emergency_contact_number,
                $this->convertGenderToSheet($customer->gender),
                $customer->source_info,
                $this->convertStatusToSheet($customer->status),
                $phones[0] ?? '',
                $phones[1] ?? '',
                $customer->updated_at->toISOString()
            ];
        }

        return response()->json(['values' => $values]);
    }
}

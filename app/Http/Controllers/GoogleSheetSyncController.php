<?php

namespace App\Http\Controllers;

use App\Http\Services\GoogleSheetsServices;
use App\Models\SyncLog;
use App\Models\User;
use App\Models\UserPhoneNumber;
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
        if (empty($statusValue)) return 'active';

        $status = strtolower(trim($statusValue));
        if (in_array($status, ['active', 'aktif'])) {
            return 'active';
        } elseif (in_array($status, ['inactive', 'nonaktif', 'blacklist', 'banned'])) {
            return 'blacklist';
        }
        return 'active'; // default fallback
    }

    /**
     * Convert status from database to Sheet format
     */
    private function convertStatusToSheet($statusValue)
    {
        if ($statusValue === 'active') return 'Active';
        if ($statusValue === 'blacklist') return 'Inactive';
        return 'Active';
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

            Log::info('Sync started: ' . count($rows) . ' rows to process');
            Log::info('Headers received: ' . json_encode($headers));

            DB::transaction(function () use ($headers, $rows) {
                foreach ($rows as $row) {
                    $rowData = array_combine($headers, array_pad($row, count($headers), null));

                    // Pastikan email tersedia sebelum melanjutkan
                    if (empty($rowData['Email Address'])) {
                        continue;
                    }
                    $incomingUpdatedAt = Carbon::parse($rowData['updated_at'] ?? now());

                    $user = User::where('email', $rowData['Email Address'])->first();

                    if ($user && $user->updated_at->gte($incomingUpdatedAt)) {
                        continue; // Data lokal lebih baru, lewati
                    }


                    // Use flexible column mapping for better compatibility
                    $gender = $this->convertGenderToDb($this->getColumnValue($rowData, ['Jenis Kelamin']));
                    $statusRaw = $this->getColumnValue($rowData, ['Status', 'STATUS', 'status']) ?? 'active';
                    $status = $this->convertStatusToDb($statusRaw);

                    // ✅ DEBUG: Log all possible Status values
                    Log::info('📊 Processing user: ' . $rowData['Email Address']);
                    Log::info('📊 Status values - Status: ' . ($rowData['Status'] ?? 'null') . ', STATUS: ' . ($rowData['STATUS'] ?? 'null') . ', status: ' . ($rowData['status'] ?? 'null'));
                    Log::info('📊 Raw Status: ' . $statusRaw . ' -> Converted: ' . $status . ', Gender: ' . ($gender ?? 'null'));

                    $user = User::updateOrCreate(
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
                            'password' => Hash::make('defaultpassword')
                        ]
                    );

                    // Simpan nomor telepon
                    $phoneNumbers = array_filter([$rowData['No. Hp1'] ?? null, $rowData['No. Hp2'] ?? null]);
                    foreach ($phoneNumbers as $phone) {
                        UserPhoneNumber::updateOrCreate(
                            ['user_id' => $user->id, 'phone_number' => $phone],
                            ['user_id' => $user->id, 'phone_number' => $phone]
                        );
                    }
                    SyncLog::create([
                        'direction' => 'from_sheet',
                        'model' => 'User',
                        'model_id' => optional($user)->id,
                        'payload' => $rowData,
                        'status' => 'success',
                        'message' => 'Synced from Google Sheet'
                    ]);
                }
            });

            return response()->json(['message' => 'Data synchronized successfully']);
        } catch (Exception $e) {
            Log::error('Google Sheet Sync Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        $lastSyncAt = $request->query('since');
        $users = User::with('userPhoneNumbers')
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

        foreach ($users as $user) {
            $phones = $user->userPhoneNumbers->pluck('phone_number')->values();

            $values[] = [
                $user->email,
                $user->name,
                $user->address,
                $user->job,
                $user->office_address,
                $user->instagram_username,
                $user->emergency_contact_name,
                $user->emergency_contact_number,
                $this->convertGenderToSheet($user->gender),
                $user->source_info,
                $this->convertStatusToSheet($user->status),
                $phones[0] ?? '',
                $phones[1] ?? '',
                $user->updated_at->toISOString()
            ];
        }

        return response()->json(['values' => $values]);
    }
}

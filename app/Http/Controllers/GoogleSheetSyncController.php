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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function sync(Request $request)
    {
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

                    $user = User::where('email', $rowData['Email Address'])->first();

                    if ($user && $user->updated_at->gte($incomingUpdatedAt)) {
                        continue; // Data lokal lebih baru, lewati
                    }


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
                            'gender' => $rowData['Jenis Kelamin'] ?? null,
                            'source_info' => $rowData['Mengetahui Global Photo Rental dari'] ?? null,
                            'status' => $rowData['STATUS'] ?? 'Aktif',
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
            'STATUS',
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
                $user->gender,
                $user->source_info,
                $user->status,
                $phones[0] ?? '',
                $phones[1] ?? '',
                $user->updated_at->toISOString()
            ];
        }

        return response()->json(['values' => $values]);
    }
}

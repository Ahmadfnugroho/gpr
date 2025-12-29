<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPhoneNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;

class CustomerSheetSyncService
{
    public function sync(array $headers, array $rows): array
    {
        Activity::disable();

        $processed = 0;
        $skipped = 0;

        DB::transaction(function () use ($headers, $rows, &$processed, &$skipped) {
            foreach ($rows as $row) {
                $rowData = array_combine($headers, array_pad($row, count($headers), null));

                if (empty($rowData['Email Address'])) {
                    $skipped++;
                    continue;
                }

                $customer = Customer::updateOrCreate(
                    ['email' => trim($rowData['Email Address'])],
                    [
                        'name' => $rowData['Nama Lengkap (Sesuai KTP)'] ?? null,
                        'address' => $rowData['Alamat Tinggal Sekarang (Ditulis Lengkap)'] ?? null,
                        'job' => $rowData['Pekerjaan'] ?? null,
                        'office_address' => $rowData['Alamat Kantor'] ?? null,
                        'instagram_username' => $rowData['Nama akun Instagram penyewa'] ?? null,
                        'emergency_contact_name' => $rowData['Nama Kontak Emergency'] ?? null,
                        'emergency_contact_number' => $rowData['No. Hp Kontak Emergency'] ?? null,
                        'gender' => $this->normalizeGender($rowData['Jenis Kelamin'] ?? null),
                        'source_info' => $rowData['Mengetahui Global Photo Rental dari'] ?? null,
                        'status' => $this->normalizeStatus($rowData['Status'] ?? null),
                        'updated_at' => Carbon::now(), // Sheet always wins
                    ]
                );

                $this->syncPhoneNumbers($customer, [
                    $rowData['No. Hp1'] ?? null,
                    $rowData['No. Hp2'] ?? null,
                ]);

                $processed++;
            }
        });

        Activity::enable();

        return [
            'processed' => $processed,
            'skipped' => $skipped,
        ];
    }

    private function normalizeGender(?string $value): ?string
    {
        if (!$value) return null;

        $v = strtolower(trim($value));
        return match (true) {
            in_array($v, ['laki-laki', 'laki laki', 'male']) => 'male',
            in_array($v, ['perempuan', 'female']) => 'female',
            default => null,
        };
    }

    private function normalizeStatus(?string $value): string
    {
        if (!$value) return Customer::STATUS_BLACKLIST;

        $v = strtolower(trim($value));
        return match (true) {
            in_array($v, ['active', 'aktif']) => Customer::STATUS_ACTIVE,
            in_array($v, ['inactive', 'nonaktif']) => Customer::STATUS_INACTIVE,
            in_array($v, ['blacklist', 'banned']) => Customer::STATUS_BLACKLIST,
            default => Customer::STATUS_BLACKLIST,
        };
    }

    private function syncPhoneNumbers(Customer $customer, array $phones): void
    {
        $phones = array_unique(array_filter($phones));

        foreach ($phones as $index => $phone) {
            CustomerPhoneNumber::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'phone_number' => $phone,
                ],
                [
                    'is_primary' => $index === 0,
                ]
            );
        }
    }
}

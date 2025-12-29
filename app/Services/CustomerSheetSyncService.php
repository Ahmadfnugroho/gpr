<?php

namespace App\Services;

use App\Services\Customer\CustomerSyncService;
use App\Services\Customer\DTO\CustomerSheetDTO;
use Carbon\Carbon;
use Spatie\Activitylog\Facades\Activity;

final class CustomerSheetSyncService
{
    public function __construct(
        protected CustomerSyncService $customerSyncService
    ) {}

    /**
     * Sinkronisasi sheet → database (incremental, batch)
     *
     * @param array<int, string> $headers
     * @param array<int, array> $rows
     * @param Carbon $lastSyncedAt
     * @param int $batchSize
     * @return array{processed: int, skipped: int, last_processed_row: ?int, synced_at: Carbon}
     */
    public function sync(
        array $headers,
        array $rows,
        Carbon $lastSyncedAt,
        int $batchSize = 100
    ): array {
        $toProcess = [];
        $processed = 0;
        $skipped   = 0;
        $lastRow   = null;

        foreach ($rows as $index => $row) {
            $rowData = array_combine(
                $headers,
                array_pad($row, count($headers), null)
            );

            // Skip jika email kosong
            if (empty($rowData['Email Address'])) {
                $skipped++;
                continue;
            }

            // Incremental: hanya row baru/update
            $rowUpdatedAt = isset($rowData['last_updated'])
                ? Carbon::parse($rowData['last_updated'])
                : null;

            if ($rowUpdatedAt && $rowUpdatedAt->lte($lastSyncedAt)) {
                continue;
            }

            // Normalisasi phones
            $phones = [];
            if (!empty($rowData['No. Hp1'])) {
                $phones[] = ['number' => $rowData['No. Hp1'], 'is_primary' => true];
            }
            if (!empty($rowData['No. Hp2'])) {
                $phones[] = ['number' => $rowData['No. Hp2'], 'is_primary' => false];
            }

            // Build DTO
            $dto = CustomerSheetDTO::fromNormalizedArray([
                'email' => $rowData['Email Address'] ?? '',
                'name' => $rowData['Nama Lengkap (Sesuai KTP)'] ?? '',
                'address' => $rowData['Alamat Tinggal Sekarang (Ditulis Lengkap)'] ?? null,
                'job' => $rowData['Pekerjaan'] ?? null,
                'office_address' => $rowData['Alamat Kantor'] ?? null,
                'gender' => $rowData['Jenis Kelamin'] ?? null,
                'status' => $rowData['Status'] ?? null,
                'source_info' => 'google_sheet',
                'instagram_username' => $rowData['Nama akun Instagram penyewa'] ?? null,
                'emergency_contact_name' => $rowData['Nama Kontak Emergency'] ?? null,
                'emergency_contact_number' => $rowData['No. Hp Kontak Emergency'] ?? null,
                'phones' => $phones,
            ]);

            $toProcess[] = $dto;
            $lastRow = $index + 1; // cursor sheet (1-based)

            // Batch flush
            if (count($toProcess) === $batchSize) {
                foreach ($toProcess as $dtoBatch) {
                    $this->customerSyncService->syncOne($dtoBatch);
                    $processed++;
                }
                $toProcess = [];
            }
        }

        // Flush sisa batch
        if (!empty($toProcess)) {
            foreach ($toProcess as $dtoBatch) {
                $this->customerSyncService->syncOne($dtoBatch);
                $processed++;
            }
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'last_processed_row' => $lastRow,
            'synced_at' => now(),
        ];
    }
}

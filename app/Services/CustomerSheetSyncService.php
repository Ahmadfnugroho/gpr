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
            if (!empty($rowData['phone1'])) $phones[] = ['number' => $rowData['phone1'], 'is_primary' => true];
            if (!empty($rowData['phone2'])) $phones[] = ['number' => $rowData['phone2'], 'is_primary' => false];

            $dto = CustomerSheetDTO::fromNormalizedArray([
                'email' => $rowData['email'] ?? '',
                'name' => $rowData['name'] ?? '',
                'address' => $rowData['address'] ?? null,
                'job' => $rowData['job'] ?? null,
                'office_address' => $rowData['office_address'] ?? null,
                'gender' => $rowData['gender'] ?? null,
                'status' => $rowData['status'] ?? null,
                'source_info' => $rowData['source_info'] ?? 'google_sheet',
                'instagram_username' => $rowData['instagram_username'] ?? null,
                'emergency_contact_name' => $rowData['emergency_contact_name'] ?? null,
                'emergency_contact_number' => $rowData['emergency_contact_number'] ?? null,
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

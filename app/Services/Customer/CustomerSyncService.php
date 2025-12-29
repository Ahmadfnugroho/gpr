<?php

namespace App\Services\Customer;

use App\Services\Customer\DTO\CustomerSheetDTO;
use Illuminate\Support\Facades\DB;

class CustomerSyncService
{
    public function __construct(
        protected CustomerProfileUpdater $profileUpdater
    ) {}

    public function sync(array $rows): array
    {
        $processed = 0;

        DB::transaction(function () use ($rows, &$processed) {
            foreach ($rows as $row) {
                $dto = CustomerSheetDTO::fromArray($row);
                $this->profileUpdater->upsert($dto);
                $processed++;
            }
        });

        return [
            'processed' => $processed,
            'synced_at' => now(),
        ];
    }
}

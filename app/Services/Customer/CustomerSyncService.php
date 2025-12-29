<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Services\Customer\DTO\CustomerSheetDTO;
use Illuminate\Support\Facades\DB;

final class CustomerSyncService
{
    public function __construct(
        protected CustomerProfileUpdater $profileUpdater,
        protected CustomerPhoneNumberSync $phoneNumberSync
    ) {}

    /**
     * Sinkronisasi SATU customer dari DTO (Sheet → DB).
     * One-way, deterministic, row-level transaction.
     */
    public function syncOne(CustomerSheetDTO $dto): Customer
    {
        return DB::transaction(function () use ($dto) {
            $customer = $this->profileUpdater->upsert($dto);

            // Sinkron nomor HP terpisah
            $this->phoneNumberSync->sync($customer, $dto->phones);

            return $customer;
        });
    }
}

<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Services\Customer\DTO\CustomerSheetDTO;

class CustomerProfileUpdater
{
    public function upsert(CustomerSheetDTO $dto): Customer
    {
        return Customer::updateOrCreate(
            ['email' => $dto->email],
            [
                'name'        => $dto->name,
                'address'     => $dto->address,
                'job'         => $dto->job,
                'gender'      => $dto->gender,
                'status'      => CustomerStatusMapper::map($dto->status),
                'source_info' => $dto->sourceInfo,
            ]
        );
    }
}

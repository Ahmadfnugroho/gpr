<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Services\Customer\DTO\CustomerSheetDTO;

final class CustomerProfileUpdater
{
    /**
     * Upsert customer profile berdasarkan data dari Google Sheet.
     * Sheet dianggap source of truth (one-way).
     */
    public function upsert(CustomerSheetDTO $dto): Customer
    {
        return Customer::updateOrCreate(
            ['email' => $dto->email],
            [
                // Identity
                'name' => $dto->name,

                // Profile
                'address'        => $dto->address,
                'job'            => $dto->job,
                'office_address' => $dto->officeAddress,
                'gender'         => $dto->gender,

                // Social & emergency
                'instagram_username'      => $dto->instagramUsername,
                'emergency_contact_name'  => $dto->emergencyContactName,
                'emergency_contact_number' => $dto->emergencyContactNumber,

                // Governance
                'status'      => CustomerStatusMapper::fromSheet($dto->status),
                'source_info' => $dto->sourceInfo,
            ]
        );
    }
}

<?php

namespace App\Services\Customer\DTO;

final class CustomerSheetDTO
{
    /**
     * @param array<int, array{number: string, is_primary: bool}> $phones
     */
    public function __construct(
        public readonly string $email,
        public readonly string $name,

        public readonly ?string $address,
        public readonly ?string $job,
        public readonly ?string $officeAddress,

        public readonly ?string $gender,
        public readonly ?string $status,
        public readonly ?string $sourceInfo,

        public readonly ?string $instagramUsername,
        public readonly ?string $emergencyContactName,
        public readonly ?string $emergencyContactNumber,

        public readonly array $phones = [],
    ) {}

    /**
     * Factory dari data hasil transform (BUKAN raw Sheet)
     */
    public static function fromNormalizedArray(array $data): self
    {
        return new self(
            email: strtolower(trim($data['email'])),
            name: trim($data['name']),

            address: $data['address'] ?? null,
            job: $data['job'] ?? null,
            officeAddress: $data['office_address'] ?? null,

            gender: $data['gender'] ?? null,
            status: $data['status'] ?? null,
            sourceInfo: $data['source_info'] ?? 'google_sheet',

            instagramUsername: $data['instagram_username'] ?? null,
            emergencyContactName: $data['emergency_contact_name'] ?? null,
            emergencyContactNumber: $data['emergency_contact_number'] ?? null,

            phones: $data['phones'] ?? [],
        );
    }
}

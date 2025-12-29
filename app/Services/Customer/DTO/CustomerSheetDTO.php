<?php

namespace App\Services\Customer\DTO;

class CustomerSheetDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $job,
        public readonly ?string $gender,
        public readonly ?string $status,
        public readonly ?string $sourceInfo,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            email: strtolower(trim($row['email'])),
            name: trim($row['name']),
            phone: $row['phone'] ?? null,
            address: $row['address'] ?? null,
            job: $row['job'] ?? null,
            gender: $row['gender'] ?? null,
            status: $row['status'] ?? null,
            sourceInfo: 'google_sheet'
        );
    }
}

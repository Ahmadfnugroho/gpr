<?php

namespace App\Services\Customer;

use App\Models\Customer;

final class CustomerStatusMapper
{
    /**
     * Map status dari Google Sheet ke enum database (canonical).
     */
    public static function fromSheet(?string $value): string
    {
        $normalized = strtolower(trim($value ?? ''));

        return match (true) {
            in_array($normalized, ['active', 'aktif']) =>
            Customer::STATUS_ACTIVE,

            in_array($normalized, ['inactive', 'nonaktif']) =>
            Customer::STATUS_INACTIVE,

            in_array($normalized, ['blacklist', 'banned', 'ban']) =>
            Customer::STATUS_BLACKLIST,

            default =>
            Customer::STATUS_BLACKLIST,
        };
    }

    /**
     * Map dari enum database ke representasi Sheet (read-only / snapshot).
     * Saat ini tidak dipakai, tapi disiapkan untuk export.
     */
    public static function toSheet(string $dbValue): string
    {
        return match ($dbValue) {
            Customer::STATUS_ACTIVE     => 'active',
            Customer::STATUS_INACTIVE   => 'inactive',
            Customer::STATUS_BLACKLIST  => 'blacklist',
            default                     => 'blacklist',
        };
    }
}

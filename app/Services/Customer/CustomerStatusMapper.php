<?php

namespace App\Services\Customer;

use App\Models\Customer;

class CustomerStatusMapper
{
    public static function map(?string $value): string
    {
        return match (strtolower($value ?? '')) {
            'active'     => Customer::STATUS_ACTIVE,
            'inactive'   => Customer::STATUS_INACTIVE,
            'blacklist'  => Customer::STATUS_BLACKLIST,
            default      => Customer::STATUS_BLACKLIST,
        };
    }
}

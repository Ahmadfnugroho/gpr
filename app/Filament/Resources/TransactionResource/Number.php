<?php

namespace App\Filament\Resources\TransactionResource;

class Number
{
    public static function currency($amount, $currency)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

<?php

namespace App\Filament\Resources\TransactionResource\FormSections\ProductList;

class Number
{
    public static function currency($amount, $currency)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

<?php

namespace App\Helpers;

class Number
{
    public static function currency($amount, $currency)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

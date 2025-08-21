<?php

class Number {
    public static function currency($amount, $currency = 'IDR') {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

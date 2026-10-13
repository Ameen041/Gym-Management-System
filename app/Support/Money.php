<?php

namespace App\Support;

class Money
{
    /** Prices are in US dollars: 50 -> "$50", 49.5 -> "$49.50". */
    public static function format($amount): string
    {
        $amount = (float) $amount;
        $decimals = floor($amount) == $amount ? 0 : 2;

        return '$' . number_format($amount, $decimals);
    }
}

<?php

namespace App\Support;

class MoneyWords
{
    public static function rupees(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paisa = (int) round(($amount - $rupees) * 100);
        $words = self::number($rupees).' rupees';
        if ($paisa > 0) {
            $words .= ' and '.self::number($paisa).' paisa';
        }

        return ucfirst($words).' only';
    }

    private static function number(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        $parts = [];
        foreach ([10000000 => 'crore', 100000 => 'lakh', 1000 => 'thousand', 100 => 'hundred'] as $value => $label) {
            if ($number >= $value) {
                $parts[] = self::number((int) floor($number / $value)).' '.$label;
                $number %= $value;
            }
        }
        if ($number >= 20) {
            $parts[] = $tens[(int) floor($number / 10)].($number % 10 ? ' '.$ones[$number % 10] : '');
        } elseif ($number > 0) {
            $parts[] = $ones[$number];
        }

        return implode(' ', $parts);
    }
}

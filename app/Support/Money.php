<?php

namespace App\Support;

class Money
{
    public static function format(int $cents): string
    {
        $negative = $cents < 0;
        $cents = abs($cents);
        $reais = intdiv($cents, 100);
        $centavos = str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
        $formatted = 'R$ '.number_format($reais, 0, ',', '.').','.$centavos;

        return $negative ? '-'.$formatted : $formatted;
    }
}

<?php

namespace App\Algorithms;

class CMGR
{
    public static function calculate(float $initial, float $final, int $months): float
    {
        if ($initial <= 0 || $final <= 0 || $months <= 0) {
            return 0.0;
        }

        return (pow($final / $initial, 1 / $months) - 1) * 100;
    }
}

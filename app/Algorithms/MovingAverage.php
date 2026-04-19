<?php

namespace App\Algorithms;

class MovingAverage
{
    public static function sma(array $values, int $window): array
    {
        $result = [];
        $window = max(1, $window);

        foreach ($values as $i => $value) {
            $start = max(0, $i - $window + 1);
            $slice = array_slice($values, $start, $i - $start + 1);
            $result[] = count($slice) ? array_sum($slice) / count($slice) : 0;
        }

        return $result;
    }

    public static function ema(array $values, float $alpha = 0.3): array
    {
        if (empty($values)) {
            return [];
        }

        $alpha = max(0.01, min(0.99, $alpha));
        $ema = [];
        $ema[0] = (float) $values[0];

        for ($i = 1, $count = count($values); $i < $count; $i++) {
            $ema[$i] = $alpha * (float) $values[$i] + (1 - $alpha) * $ema[$i - 1];
        }

        return $ema;
    }
}

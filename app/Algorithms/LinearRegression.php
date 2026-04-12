<?php

namespace App\Algorithms;

class LinearRegression
{
    public static function forecast(array $series, int $periods = 3): array
    {
        $n = count($series);
        if ($n < 2) {
            return [
                'slope' => 0,
                'intercept' => $series[0] ?? 0,
                'r_squared' => 0,
                'predictions' => [],
            ];
        }

        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($series);
        $sumXY = 0.0;
        $sumX2 = 0.0;

        foreach ($x as $index => $xValue) {
            $sumXY += $xValue * $series[$index];
            $sumX2 += $xValue ** 2;
        }

        $denominator = ($n * $sumX2) - ($sumX ** 2);
        $slope = $denominator != 0 ? (($n * $sumXY) - ($sumX * $sumY)) / $denominator : 0;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        $meanY = $sumY / $n;
        $ssTot = 0.0;
        $ssRes = 0.0;

        foreach ($x as $index => $xValue) {
            $actual = $series[$index];
            $fitted = ($slope * $xValue) + $intercept;
            $ssTot += ($actual - $meanY) ** 2;
            $ssRes += ($actual - $fitted) ** 2;
        }

        $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0;

        $predictions = [];
        for ($future = 1; $future <= $periods; $future++) {
            $xValue = $n + $future;
            $predictions[] = round(($slope * $xValue) + $intercept, 2);
        }

        return [
            'slope' => round($slope, 6),
            'intercept' => round($intercept, 2),
            'r_squared' => round($rSquared, 4),
            'predictions' => $predictions,
        ];
    }
}

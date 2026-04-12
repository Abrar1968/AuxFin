<?php

namespace App\Algorithms;

class MonteCarloForecast
{
    private const COLLECTION_PROBS = [
        '0_30d' => 0.95,
        '31_60d' => 0.80,
        '61_90d' => 0.60,
        '90plus' => 0.30,
    ];

    public static function simulate(array $arItems, int $iterations = 1000): array
    {
        $iterations = max(50, $iterations);
        $totals = [];

        for ($i = 0; $i < $iterations; $i++) {
            $total = 0.0;

            foreach ($arItems as $item) {
                $amount = (float) ($item['amount'] ?? 0);
                $bucket = (string) ($item['bucket'] ?? '90plus');
                $probability = self::COLLECTION_PROBS[$bucket] ?? self::COLLECTION_PROBS['90plus'];

                if (mt_rand() / mt_getrandmax() <= $probability) {
                    $total += $amount;
                }
            }

            $totals[] = $total;
        }

        sort($totals);

        return [
            'p10' => round(self::percentile($totals, 0.10), 2),
            'p50' => round(self::percentile($totals, 0.50), 2),
            'p90' => round(self::percentile($totals, 0.90), 2),
            'simulations' => $iterations,
        ];
    }

    private static function percentile(array $sortedValues, float $percentile): float
    {
        $count = count($sortedValues);
        if ($count === 0) {
            return 0;
        }

        $index = (int) floor(($count - 1) * $percentile);

        return $sortedValues[$index];
    }
}

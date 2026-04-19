<?php

namespace App\Algorithms;

class ZScoreAnomaly
{
    public static function detect(array $values, float $threshold = 2.5): array
    {
        $count = count($values);
        if ($count === 0) {
            return [];
        }

        $mean = array_sum($values) / $count;
        $variance = 0.0;

        foreach ($values as $value) {
            $variance += ($value - $mean) ** 2;
        }

        $stdDev = sqrt($variance / max(1, $count));

        return array_map(static function ($value) use ($mean, $stdDev, $threshold) {
            $zScore = $stdDev > 0 ? ($value - $mean) / $stdDev : 0.0;

            return [
                'value' => $value,
                'z_score' => round($zScore, 4),
                'is_anomaly' => abs($zScore) > $threshold,
            ];
        }, $values);
    }
}

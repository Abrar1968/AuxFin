<?php

namespace App\Algorithms;

class ARHealthScore
{
    private const WEIGHTS = [
        '0_30d' => 0.95,
        '31_60d' => 0.80,
        '61_90d' => 0.60,
        '90plus' => 0.30,
    ];

    public static function calculate(array $items): array
    {
        $weighted = 0.0;
        $total = 0.0;

        foreach ($items as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            $bucket = (string) ($item['bucket'] ?? '90plus');
            $weight = self::WEIGHTS[$bucket] ?? self::WEIGHTS['90plus'];
            $weighted += $amount * $weight;
            $total += $amount;
        }

        $score = $total > 0 ? ($weighted / $total) * 100 : 0;

        return [
            'score' => round($score, 2),
            'status' => self::status($score),
        ];
    }

    private static function status(float $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'watch',
            default => 'critical',
        };
    }
}

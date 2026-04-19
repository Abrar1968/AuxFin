<?php

namespace App\Support;

use Carbon\Carbon;

class TimeframeRange
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED = ['day', 'week', 'month', 'year'];

    public static function normalize(?string $timeframe, string $default = 'month'): string
    {
        $candidate = strtolower((string) $timeframe);

        if (in_array($candidate, self::ALLOWED, true)) {
            return $candidate;
        }

        return in_array($default, self::ALLOWED, true) ? $default : 'month';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function bounds(string $timeframe, ?string $anchorDate = null): array
    {
        $resolvedTimeframe = self::normalize($timeframe);
        $anchor = $anchorDate
            ? Carbon::parse($anchorDate)->startOfDay()
            : now()->startOfDay();

        return match ($resolvedTimeframe) {
            'day' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'week' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'year' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function historyBounds(string $timeframe, int $periods, ?string $anchorDate = null): array
    {
        $resolvedTimeframe = self::normalize($timeframe);
        $resolvedPeriods = max(1, $periods);

        [$periodStart, $periodEnd] = self::bounds($resolvedTimeframe, $anchorDate);
        $historyStart = $periodStart->copy();

        if ($resolvedPeriods > 1) {
            $steps = $resolvedPeriods - 1;

            $historyStart = match ($resolvedTimeframe) {
                'day' => $historyStart->subDays($steps)->startOfDay(),
                'week' => $historyStart->subWeeks($steps)->startOfWeek(),
                'year' => $historyStart->subYears($steps)->startOfYear(),
                default => $historyStart->subMonths($steps)->startOfMonth(),
            };
        }

        return [$historyStart, $periodEnd->copy()];
    }

    /**
     * @return array<int, array{key: string, label: string, start: Carbon, end: Carbon}>
     */
    public static function periods(string $timeframe, Carbon $from, Carbon $to): array
    {
        $resolvedTimeframe = self::normalize($timeframe);

        $rangeFrom = $from->copy()->startOfDay();
        $rangeTo = $to->copy()->endOfDay();

        if ($rangeFrom->greaterThan($rangeTo)) {
            [$rangeFrom, $rangeTo] = [$rangeTo->copy()->startOfDay(), $rangeFrom->copy()->endOfDay()];
        }

        $cursor = self::periodStart($resolvedTimeframe, $rangeFrom);
        $periods = [];

        while ($cursor->lessThanOrEqualTo($rangeTo)) {
            $windowStart = $cursor->copy();
            $windowEnd = self::periodEnd($resolvedTimeframe, $cursor);

            if ($windowEnd->lessThan($rangeFrom)) {
                $cursor = self::nextPeriodStart($resolvedTimeframe, $cursor);

                continue;
            }

            if ($windowStart->greaterThan($rangeTo)) {
                break;
            }

            $effectiveStart = $windowStart->greaterThan($rangeFrom) ? $windowStart->copy() : $rangeFrom->copy();
            $effectiveEnd = $windowEnd->lessThan($rangeTo) ? $windowEnd->copy() : $rangeTo->copy();

            $periods[] = [
                'key' => self::key($resolvedTimeframe, $windowStart),
                'label' => self::label($resolvedTimeframe, $windowStart),
                'start' => $effectiveStart,
                'end' => $effectiveEnd,
            ];

            $cursor = self::nextPeriodStart($resolvedTimeframe, $cursor);
        }

        return $periods;
    }

    public static function key(string $timeframe, Carbon $periodStart): string
    {
        $resolvedTimeframe = self::normalize($timeframe);

        return match ($resolvedTimeframe) {
            'day' => $periodStart->format('Y-m-d'),
            'week' => sprintf('%s-W%02d', $periodStart->format('o'), (int) $periodStart->format('W')),
            'year' => $periodStart->format('Y'),
            default => $periodStart->format('Y-m'),
        };
    }

    public static function label(string $timeframe, Carbon $periodStart): string
    {
        $resolvedTimeframe = self::normalize($timeframe);

        return match ($resolvedTimeframe) {
            'day' => $periodStart->format('Y-m-d'),
            'week' => sprintf('%s W%02d', $periodStart->format('o'), (int) $periodStart->format('W')),
            'year' => $periodStart->format('Y'),
            default => $periodStart->format('Y-m'),
        };
    }

    private static function periodStart(string $timeframe, Carbon $value): Carbon
    {
        return match ($timeframe) {
            'day' => $value->copy()->startOfDay(),
            'week' => $value->copy()->startOfWeek(),
            'year' => $value->copy()->startOfYear(),
            default => $value->copy()->startOfMonth(),
        };
    }

    private static function periodEnd(string $timeframe, Carbon $value): Carbon
    {
        return match ($timeframe) {
            'day' => $value->copy()->endOfDay(),
            'week' => $value->copy()->endOfWeek(),
            'year' => $value->copy()->endOfYear(),
            default => $value->copy()->endOfMonth(),
        };
    }

    private static function nextPeriodStart(string $timeframe, Carbon $value): Carbon
    {
        return match ($timeframe) {
            'day' => $value->copy()->addDay()->startOfDay(),
            'week' => $value->copy()->addWeek()->startOfWeek(),
            'year' => $value->copy()->addYear()->startOfYear(),
            default => $value->copy()->addMonth()->startOfMonth(),
        };
    }
}

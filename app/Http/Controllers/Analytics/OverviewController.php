<?php

namespace App\Http\Controllers\Analytics;

use App\Algorithms\CMGR;
use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Services\TimeframeMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OverviewController extends Controller
{
    public function __construct(private readonly TimeframeMetricsService $timeframeMetrics)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $timeframe = (string) $request->query('timeframe', 'month');
        $anchorDate = $request->query('anchor_date');
        $points = $request->has('points') ? $request->integer('points') : null;

        $cacheKey = sprintf(
            'analytics:overview:index:%s:%s:%s',
            $timeframe,
            is_string($anchorDate) ? $anchorDate : 'auto',
            $points ?? 'auto',
        );

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($timeframe, $anchorDate, $points): array {
            $seriesPayload = $this->timeframeMetrics->series(
                $timeframe,
                is_string($anchorDate) ? $anchorDate : null,
                $points,
            );

            $series = collect($seriesPayload['series'] ?? [])->values();

            return [
                'timeframe' => $seriesPayload['timeframe'] ?? 'month',
                'period_from' => $seriesPayload['period_from'] ?? null,
                'period_to' => $seriesPayload['period_to'] ?? null,
                'latest' => $series->last(),
                'series' => $series->all(),
            ];
        });

        event(new InsightStreamed('insight.analytics.overview', [
            'scope' => 'analytics',
            'timeframe' => $data['timeframe'] ?? 'month',
            'series_points' => count($data['series'] ?? []),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function cmgr(Request $request): JsonResponse
    {
        $timeframe = (string) $request->query('timeframe', 'month');
        $anchorDate = $request->query('anchor_date');
        $points = $request->has('points') ? $request->integer('points') : null;

        $cacheKey = sprintf(
            'analytics:overview:cmgr:%s:%s:%s',
            $timeframe,
            is_string($anchorDate) ? $anchorDate : 'auto',
            $points ?? 'auto',
        );

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($timeframe, $anchorDate, $points): array {
            $seriesPayload = $this->timeframeMetrics->series(
                $timeframe,
                is_string($anchorDate) ? $anchorDate : null,
                $points,
            );

            $snapshots = collect($seriesPayload['series'] ?? []);

            if ($snapshots->count() < 2) {
                return [
                    'timeframe' => $seriesPayload['timeframe'] ?? 'month',
                    'period_from' => $seriesPayload['period_from'] ?? null,
                    'period_to' => $seriesPayload['period_to'] ?? null,
                    'revenue_cmgr' => 0,
                    'payroll_cmgr' => 0,
                    'opex_cmgr' => 0,
                    'net_profit_cmgr' => 0,
                    'headcount_cmgr' => 0,
                    'ar_cmgr' => 0,
                ];
            }

            $first = $snapshots->first();
            $last = $snapshots->last();
            $periodCount = max(1, $snapshots->count() - 1);

            return [
                'timeframe' => $seriesPayload['timeframe'] ?? 'month',
                'period_from' => $seriesPayload['period_from'] ?? null,
                'period_to' => $seriesPayload['period_to'] ?? null,
                'revenue_cmgr' => round(CMGR::calculate((float) ($first['total_revenue'] ?? 0), (float) ($last['total_revenue'] ?? 0), $periodCount), 2),
                'payroll_cmgr' => round(CMGR::calculate((float) ($first['total_payroll'] ?? 0), (float) ($last['total_payroll'] ?? 0), $periodCount), 2),
                'opex_cmgr' => round(CMGR::calculate((float) ($first['total_opex'] ?? 0), (float) ($last['total_opex'] ?? 0), $periodCount), 2),
                'net_profit_cmgr' => round(CMGR::calculate((float) ($first['net_profit'] ?? 0), (float) ($last['net_profit'] ?? 0), $periodCount), 2),
                'headcount_cmgr' => round(CMGR::calculate((float) max(1, (int) ($first['headcount'] ?? 0)), (float) max(1, (int) ($last['headcount'] ?? 0)), $periodCount), 2),
                'ar_cmgr' => round(CMGR::calculate((float) max(1, (float) ($first['total_ar'] ?? 0)), (float) max(1, (float) ($last['total_ar'] ?? 0)), $periodCount), 2),
            ];
        });

        event(new InsightStreamed('insight.analytics.cmgr', [
            'scope' => 'analytics',
            'timeframe' => $data['timeframe'] ?? 'month',
            'revenue_cmgr' => (float) ($data['revenue_cmgr'] ?? 0),
            'payroll_cmgr' => (float) ($data['payroll_cmgr'] ?? 0),
            'net_profit_cmgr' => (float) ($data['net_profit_cmgr'] ?? 0),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }
}

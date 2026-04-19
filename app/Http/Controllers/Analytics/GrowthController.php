<?php

namespace App\Http\Controllers\Analytics;

use App\Algorithms\CMGR;
use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Services\ForecastService;
use App\Services\TimeframeMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GrowthController extends Controller
{
    public function __construct(
        private readonly ForecastService $forecastService,
        private readonly TimeframeMetricsService $timeframeMetrics,
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $timeframe = (string) $request->query('timeframe', 'month');
        $anchorDate = $request->query('anchor_date');
        $points = $request->has('points') ? $request->integer('points') : null;

        $cacheKey = sprintf(
            'analytics:growth:index:%s:%s:%s',
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

            $snapshots = collect($seriesPayload['series'] ?? [])->values();

            if ($snapshots->count() < 2) {
                return [
                    'timeframe' => $seriesPayload['timeframe'] ?? 'month',
                    'period_from' => $seriesPayload['period_from'] ?? null,
                    'period_to' => $seriesPayload['period_to'] ?? null,
                    'velocity' => [],
                    'series' => $snapshots->all(),
                    'efficiency_ratio' => 0,
                    'revenue_quality_score' => 0,
                    'payroll_efficiency' => [
                        'revenue_per_employee' => 0,
                        'payroll_ratio' => 0,
                        'status' => 'watch',
                    ],
                ];
            }

            $first = $snapshots->first();
            $last = $snapshots->last();
            $periodCount = max(1, $snapshots->count() - 1);

            $revenueCmgr = CMGR::calculate((float) ($first['total_revenue'] ?? 0), (float) ($last['total_revenue'] ?? 0), $periodCount);
            $headcountCmgr = CMGR::calculate(
                (float) max(1, (int) ($first['headcount'] ?? 0)),
                (float) max(1, (int) ($last['headcount'] ?? 0)),
                $periodCount,
            );

            $lastRevenue = (float) ($last['total_revenue'] ?? 0);
            $lastAr = (float) ($last['total_ar'] ?? 0);
            $lastPayroll = (float) ($last['total_payroll'] ?? 0);
            $lastHeadcount = (int) ($last['headcount'] ?? 0);

            return [
                'timeframe' => $seriesPayload['timeframe'] ?? 'month',
                'period_from' => $seriesPayload['period_from'] ?? null,
                'period_to' => $seriesPayload['period_to'] ?? null,
                'velocity' => [
                    'revenue_cmgr' => round($revenueCmgr, 2),
                    'headcount_cmgr' => round($headcountCmgr, 2),
                    'payroll_cmgr' => round(CMGR::calculate((float) ($first['total_payroll'] ?? 0), (float) ($last['total_payroll'] ?? 0), $periodCount), 2),
                    'net_profit_cmgr' => round(CMGR::calculate((float) ($first['net_profit'] ?? 0), (float) ($last['net_profit'] ?? 0), $periodCount), 2),
                    'opex_cmgr' => round(CMGR::calculate((float) ($first['total_opex'] ?? 0), (float) ($last['total_opex'] ?? 0), $periodCount), 2),
                    'ar_cmgr' => round(CMGR::calculate((float) max(1, (float) ($first['total_ar'] ?? 0)), (float) max(1, (float) ($last['total_ar'] ?? 0)), $periodCount), 2),
                ],
                'series' => $snapshots->all(),
                'efficiency_ratio' => $headcountCmgr != 0 ? round($revenueCmgr / $headcountCmgr, 2) : 0,
                'revenue_quality_score' => $lastRevenue > 0
                    ? round((($lastRevenue - $lastAr) / $lastRevenue) * 100, 2)
                    : 0,
                'payroll_efficiency' => $this->forecastService->payrollEfficiency(
                    $lastRevenue,
                    $lastPayroll,
                    $lastHeadcount,
                ),
            ];
        });

        event(new InsightStreamed('insight.analytics.growth', [
            'scope' => 'analytics',
            'timeframe' => $data['timeframe'] ?? 'month',
            'revenue_quality_score' => (float) ($data['revenue_quality_score'] ?? 0),
            'efficiency_ratio' => (float) ($data['efficiency_ratio'] ?? 0),
            'payroll_efficiency_status' => $data['payroll_efficiency']['status'] ?? 'watch',
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }
}

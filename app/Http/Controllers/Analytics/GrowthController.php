<?php

namespace App\Http\Controllers\Analytics;

use App\Algorithms\CMGR;
use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Models\CompanySnapshot;
use App\Services\ForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class GrowthController extends Controller
{
    public function __construct(private readonly ForecastService $forecastService)
    {
    }

    public function index(): JsonResponse
    {
        $data = Cache::remember('analytics:growth:index', now()->addMinutes(10), function (): array {
            $snapshots = CompanySnapshot::query()->orderBy('snapshot_month')->get();

            if ($snapshots->count() < 2) {
                return [
                    'velocity' => [],
                    'series' => $snapshots,
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
            $months = max(1, $snapshots->count() - 1);

            $revenueCmgr = CMGR::calculate((float) $first->total_revenue, (float) $last->total_revenue, $months);
            $headcountCmgr = CMGR::calculate((float) max(1, $first->headcount), (float) max(1, $last->headcount), $months);

            return [
                'velocity' => [
                    'revenue_cmgr' => round($revenueCmgr, 2),
                    'headcount_cmgr' => round($headcountCmgr, 2),
                    'payroll_cmgr' => round(CMGR::calculate((float) $first->total_payroll, (float) $last->total_payroll, $months), 2),
                    'net_profit_cmgr' => round(CMGR::calculate((float) $first->net_profit, (float) $last->net_profit, $months), 2),
                    'opex_cmgr' => round(CMGR::calculate((float) $first->total_opex, (float) $last->total_opex, $months), 2),
                    'ar_cmgr' => round(CMGR::calculate((float) max(1, $first->total_ar), (float) max(1, $last->total_ar), $months), 2),
                ],
                'series' => $snapshots,
                'efficiency_ratio' => $headcountCmgr != 0 ? round($revenueCmgr / $headcountCmgr, 2) : 0,
                'revenue_quality_score' => $last->total_revenue > 0
                    ? round((($last->total_revenue - $last->total_ar) / $last->total_revenue) * 100, 2)
                    : 0,
                'payroll_efficiency' => $this->forecastService->payrollEfficiency(
                    (float) $last->total_revenue,
                    (float) $last->total_payroll,
                    (int) $last->headcount,
                ),
            ];
        });

        event(new InsightStreamed('insight.analytics.growth', [
            'scope' => 'analytics',
            'revenue_quality_score' => (float) ($data['revenue_quality_score'] ?? 0),
            'efficiency_ratio' => (float) ($data['efficiency_ratio'] ?? 0),
            'payroll_efficiency_status' => $data['payroll_efficiency']['status'] ?? 'watch',
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }
}

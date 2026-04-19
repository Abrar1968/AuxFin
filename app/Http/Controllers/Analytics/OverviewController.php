<?php

namespace App\Http\Controllers\Analytics;

use App\Algorithms\CMGR;
use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Models\CompanySnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class OverviewController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::remember('analytics:overview:index', now()->addMinutes(10), function (): array {
            $latest = CompanySnapshot::query()->latest('snapshot_month')->first();
            $series = CompanySnapshot::query()
                ->latest('snapshot_month')
                ->limit(12)
                ->get()
                ->reverse()
                ->values();

            return [
                'latest' => $latest,
                'series' => $series,
            ];
        });

        event(new InsightStreamed('insight.analytics.overview', [
            'scope' => 'analytics',
            'series_points' => count($data['series'] ?? []),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function cmgr(): JsonResponse
    {
        $data = Cache::remember('analytics:overview:cmgr', now()->addMinutes(10), function (): array {
            $snapshots = CompanySnapshot::query()->orderBy('snapshot_month')->get();

            if ($snapshots->count() < 2) {
                return [
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
            $months = max(1, $snapshots->count() - 1);

            return [
                'revenue_cmgr' => round(CMGR::calculate((float) $first->total_revenue, (float) $last->total_revenue, $months), 2),
                'payroll_cmgr' => round(CMGR::calculate((float) $first->total_payroll, (float) $last->total_payroll, $months), 2),
                'opex_cmgr' => round(CMGR::calculate((float) $first->total_opex, (float) $last->total_opex, $months), 2),
                'net_profit_cmgr' => round(CMGR::calculate((float) $first->net_profit, (float) $last->net_profit, $months), 2),
                'headcount_cmgr' => round(CMGR::calculate((float) max(1, $first->headcount), (float) max(1, $last->headcount), $months), 2),
                'ar_cmgr' => round(CMGR::calculate((float) max(1, $first->total_ar), (float) max(1, $last->total_ar), $months), 2),
            ];
        });

        event(new InsightStreamed('insight.analytics.cmgr', [
            'scope' => 'analytics',
            'revenue_cmgr' => (float) ($data['revenue_cmgr'] ?? 0),
            'payroll_cmgr' => (float) ($data['payroll_cmgr'] ?? 0),
            'net_profit_cmgr' => (float) ($data['net_profit_cmgr'] ?? 0),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }
}

<?php

namespace App\Http\Controllers\Analytics;

use App\Algorithms\CMGR;
use App\Http\Controllers\Controller;
use App\Models\CompanySnapshot;
use Illuminate\Http\JsonResponse;

class OverviewController extends Controller
{
    public function index(): JsonResponse
    {
        $latest = CompanySnapshot::query()->latest('snapshot_month')->first();
        $series = CompanySnapshot::query()
            ->orderBy('snapshot_month')
            ->limit(12)
            ->get();

        return response()->json([
            'latest' => $latest,
            'series' => $series,
        ]);
    }

    public function cmgr(): JsonResponse
    {
        $snapshots = CompanySnapshot::query()->orderBy('snapshot_month')->get();

        if ($snapshots->count() < 2) {
            return response()->json([
                'revenue_cmgr' => 0,
                'payroll_cmgr' => 0,
                'opex_cmgr' => 0,
                'net_profit_cmgr' => 0,
                'headcount_cmgr' => 0,
                'ar_cmgr' => 0,
            ]);
        }

        $first = $snapshots->first();
        $last = $snapshots->last();
        $months = max(1, $snapshots->count() - 1);

        return response()->json([
            'revenue_cmgr' => round(CMGR::calculate((float) $first->total_revenue, (float) $last->total_revenue, $months), 2),
            'payroll_cmgr' => round(CMGR::calculate((float) $first->total_payroll, (float) $last->total_payroll, $months), 2),
            'opex_cmgr' => round(CMGR::calculate((float) $first->total_opex, (float) $last->total_opex, $months), 2),
            'net_profit_cmgr' => round(CMGR::calculate((float) $first->net_profit, (float) $last->net_profit, $months), 2),
            'headcount_cmgr' => round(CMGR::calculate((float) max(1, $first->headcount), (float) max(1, $last->headcount), $months), 2),
            'ar_cmgr' => round(CMGR::calculate((float) max(1, $first->total_ar), (float) max(1, $last->total_ar), $months), 2),
        ]);
    }
}

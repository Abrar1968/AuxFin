<?php

namespace App\Services;

use App\Models\CompanySnapshot;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\ProjectPayment;
use App\Models\SalaryMonth;
use App\Support\TimeframeRange;
use Carbon\Carbon;

class TimeframeMetricsService
{
    /**
     * @var array<int, string>
     */
    private const ACCRUAL_INVOICE_STATUSES = ['sent', 'partial', 'paid', 'overdue'];

    /**
     * @var array<string, int>
     */
    private const DEFAULT_POINTS = [
        'day' => 30,
        'week' => 12,
        'month' => 12,
        'year' => 5,
    ];

    /**
     * @return array{timeframe: string, period_from: string, period_to: string, points: int, series: array<int, array<string, mixed>>}
     */
    public function series(string $timeframe = 'month', ?string $anchorDate = null, ?int $points = null): array
    {
        $window = $this->resolveWindow($timeframe, $anchorDate, $points);
        $series = [];

        if (in_array($window['timeframe'], ['month', 'year'], true)) {
            $series = $this->snapshotSeries($window['timeframe'], $window['from'], $window['to']);
        }

        if (count($series) === 0) {
            foreach (TimeframeRange::periods($window['timeframe'], $window['from'], $window['to']) as $period) {
                $periodStart = $period['start'];
                $periodEnd = $period['end'];

                $revenue = (float) Invoice::query()
                    ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
                    ->whereBetween('invoice_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->sum('amount');

                $cashCollected = (float) ProjectPayment::query()
                    ->whereBetween('payment_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->sum('amount');

                $payroll = $this->payrollForPeriod($periodStart, $periodEnd);

                $opex = (float) Expense::query()
                    ->whereBetween('expense_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->sum('amount');

                $liabilityCost = $this->liabilityCostForPeriod($periodStart, $periodEnd);

                $grossProfit = $revenue - $payroll;
                $netProfit = $grossProfit - $opex - $liabilityCost;

                $accruedToDate = (float) Invoice::query()
                    ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
                    ->whereDate('invoice_date', '<=', $periodEnd->toDateString())
                    ->sum('amount');

                $linkedCollectionsToDate = (float) ProjectPayment::query()
                    ->whereNotNull('invoice_id')
                    ->whereDate('payment_date', '<=', $periodEnd->toDateString())
                    ->sum('amount');

                $headcount = (int) Employee::query()
                    ->where(function ($query) use ($periodEnd): void {
                        $query->whereNull('date_of_joining')
                            ->orWhereDate('date_of_joining', '<=', $periodEnd->toDateString());
                    })
                    ->count();

                $series[] = [
                    'timeframe' => $window['timeframe'],
                    'period' => $period['label'],
                    'period_key' => $period['key'],
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'snapshot_month' => $periodStart->toDateString(),
                    'total_revenue' => round($revenue, 2),
                    'total_cash_collected' => round($cashCollected, 2),
                    'total_payroll' => round($payroll, 2),
                    'total_opex' => round($opex, 2),
                    'liability_cost' => round($liabilityCost, 2),
                    'gross_profit' => round($grossProfit, 2),
                    'net_profit' => round($netProfit, 2),
                    'burn_rate' => round(max(0, $payroll + $opex + $liabilityCost - $cashCollected), 2),
                    'headcount' => $headcount,
                    'total_ar' => round(max(0, $accruedToDate - $linkedCollectionsToDate), 2),
                ];
            }
        }

        return [
            'timeframe' => $window['timeframe'],
            'period_from' => $window['from']->toDateString(),
            'period_to' => $window['to']->toDateString(),
            'points' => count($series),
            'series' => $series,
        ];
    }

    /**
     * @return array{timeframe: string, from: Carbon, to: Carbon, points: int}
     */
    public function resolveWindow(string $timeframe = 'month', ?string $anchorDate = null, ?int $points = null): array
    {
        $resolvedTimeframe = TimeframeRange::normalize($timeframe);
        $defaultPoints = self::DEFAULT_POINTS[$resolvedTimeframe] ?? 12;
        $resolvedPoints = max(1, min(120, $points ?? $defaultPoints));

        [$from, $to] = TimeframeRange::historyBounds($resolvedTimeframe, $resolvedPoints, $anchorDate);

        return [
            'timeframe' => $resolvedTimeframe,
            'from' => $from,
            'to' => $to,
            'points' => $resolvedPoints,
        ];
    }

    private function payrollForPeriod(Carbon $periodStart, Carbon $periodEnd): float
    {
        $monthStart = $periodStart->copy()->startOfMonth();
        $monthEnd = $periodEnd->copy()->startOfMonth();

        $rows = SalaryMonth::query()
            ->whereIn('status', ['processed', 'paid'])
            ->whereDate('month', '>=', $monthStart->toDateString())
            ->whereDate('month', '<=', $monthEnd->toDateString())
            ->get(['month', 'gross_earnings']);

        $total = 0.0;

        foreach ($rows as $row) {
            $salaryMonthStart = Carbon::parse($row->month)->startOfMonth();
            $salaryMonthEnd = $salaryMonthStart->copy()->endOfMonth();

            [$activeDays, $daysInMonth] = $this->overlapDays($periodStart, $periodEnd, $salaryMonthStart, $salaryMonthEnd);
            if ($activeDays <= 0) {
                continue;
            }

            $total += ((float) $row->gross_earnings) * ($activeDays / $daysInMonth);
        }

        return round($total, 2);
    }

    private function liabilityCostForPeriod(Carbon $periodStart, Carbon $periodEnd): float
    {
        $cursor = $periodStart->copy()->startOfMonth();
        $windowEnd = $periodEnd->copy()->startOfMonth();
        $total = 0.0;

        while ($cursor->lessThanOrEqualTo($windowEnd)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            $monthlyCost = (float) Liability::query()
                ->whereDate('start_date', '<=', $monthEnd->toDateString())
                ->where(function ($query) use ($monthStart): void {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $monthStart->toDateString());
                })
                ->sum('monthly_payment');

            if ($monthlyCost > 0) {
                [$activeDays, $daysInMonth] = $this->overlapDays($periodStart, $periodEnd, $monthStart, $monthEnd);
                if ($activeDays > 0) {
                    $total += $monthlyCost * ($activeDays / $daysInMonth);
                }
            }

            $cursor->addMonth();
        }

        return round($total, 2);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function overlapDays(Carbon $from, Carbon $to, Carbon $windowStart, Carbon $windowEnd): array
    {
        $start = $from->greaterThan($windowStart) ? $from->copy()->startOfDay() : $windowStart->copy()->startOfDay();
        $end = $to->lessThan($windowEnd) ? $to->copy()->endOfDay() : $windowEnd->copy()->endOfDay();

        if ($start->greaterThan($end)) {
            return [0, max(1, (int) $windowStart->daysInMonth)];
        }

        $activeDays = (int) $start->diffInDays($end) + 1;

        return [$activeDays, max(1, (int) $windowStart->daysInMonth)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function snapshotSeries(string $timeframe, Carbon $from, Carbon $to): array
    {
        $snapshots = CompanySnapshot::query()
            ->whereDate('snapshot_month', '>=', $from->copy()->startOfMonth()->toDateString())
            ->whereDate('snapshot_month', '<=', $to->copy()->endOfMonth()->toDateString())
            ->orderBy('snapshot_month')
            ->get();

        if ($snapshots->isEmpty()) {
            return [];
        }

        if ($timeframe === 'month') {
            return $snapshots
                ->map(fn (CompanySnapshot $snapshot): array => $this->mapMonthlySnapshot($snapshot))
                ->values()
                ->all();
        }

        return $snapshots
            ->groupBy(fn (CompanySnapshot $snapshot): string => Carbon::parse($snapshot->snapshot_month)->format('Y'))
            ->map(function ($group, $year): array {
                $sorted = $group->sortBy('snapshot_month')->values();
                /** @var CompanySnapshot $last */
                $last = $sorted->last();

                $totalRevenue = (float) $sorted->sum('total_revenue');
                $totalCash = (float) $sorted->sum('total_cash_collected');
                $totalPayroll = (float) $sorted->sum('total_payroll');
                $totalOpex = (float) $sorted->sum('total_opex');
                $grossProfit = (float) $sorted->sum('gross_profit');
                $netProfit = (float) $sorted->sum('net_profit');
                $liabilityCost = max(0, $grossProfit - $netProfit - $totalOpex);
                $periodStart = Carbon::createFromDate((int) $year, 1, 1)->startOfDay();
                $periodEnd = Carbon::createFromDate((int) $year, 12, 31)->endOfDay();

                return [
                    'timeframe' => 'year',
                    'period' => (string) $year,
                    'period_key' => (string) $year,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'snapshot_month' => $periodStart->toDateString(),
                    'total_revenue' => round($totalRevenue, 2),
                    'total_cash_collected' => round($totalCash, 2),
                    'total_payroll' => round($totalPayroll, 2),
                    'total_opex' => round($totalOpex, 2),
                    'liability_cost' => round($liabilityCost, 2),
                    'gross_profit' => round($grossProfit, 2),
                    'net_profit' => round($netProfit, 2),
                    'burn_rate' => round((float) $sorted->avg('burn_rate'), 2),
                    'headcount' => (int) ($last->headcount ?? 0),
                    'total_ar' => round((float) ($last->total_ar ?? 0), 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMonthlySnapshot(CompanySnapshot $snapshot): array
    {
        $periodStart = Carbon::parse($snapshot->snapshot_month)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $liabilityCost = max(0, (float) $snapshot->gross_profit - (float) $snapshot->net_profit - (float) $snapshot->total_opex);

        return [
            'id' => $snapshot->id,
            'timeframe' => 'month',
            'period' => $periodStart->format('Y-m'),
            'period_key' => $periodStart->format('Y-m'),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'snapshot_month' => $periodStart->toDateString(),
            'total_revenue' => round((float) $snapshot->total_revenue, 2),
            'total_cash_collected' => round((float) $snapshot->total_cash_collected, 2),
            'total_payroll' => round((float) $snapshot->total_payroll, 2),
            'total_opex' => round((float) $snapshot->total_opex, 2),
            'liability_cost' => round($liabilityCost, 2),
            'gross_profit' => round((float) $snapshot->gross_profit, 2),
            'net_profit' => round((float) $snapshot->net_profit, 2),
            'burn_rate' => round((float) $snapshot->burn_rate, 2),
            'headcount' => (int) $snapshot->headcount,
            'total_ar' => round((float) $snapshot->total_ar, 2),
        ];
    }
}

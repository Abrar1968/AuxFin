<?php

namespace App\Services;

use App\Models\CompanySnapshot;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\SalaryMonth;
use Carbon\Carbon;

class SnapshotService
{
    public function capture(?string $month = null): CompanySnapshot
    {
        $monthDate = $month
            ? Carbon::parse($month)->startOfMonth()
            : now()->startOfMonth();

        $totalRevenue = (float) Invoice::query()
            ->whereNotNull('payment_completed_at')
            ->whereYear('payment_completed_at', $monthDate->year)
            ->whereMonth('payment_completed_at', $monthDate->month)
            ->sum('amount');

        $totalPayroll = (float) SalaryMonth::query()
            ->whereDate('month', $monthDate->toDateString())
            ->sum('net_payable');

        $totalOpex = (float) Expense::query()
            ->whereYear('expense_date', $monthDate->year)
            ->whereMonth('expense_date', $monthDate->month)
            ->sum('amount');

        $liabilityCost = (float) Liability::query()
            ->where('status', 'active')
            ->sum('monthly_payment');

        $grossProfit = $totalRevenue - $totalPayroll;
        $netProfit = $grossProfit - $totalOpex - $liabilityCost;
        $burnRate = $this->calculateBurnRate();
        $headcount = Employee::query()->count();
        $totalAr = (float) Invoice::query()
            ->whereNull('payment_completed_at')
            ->sum('amount');

        return CompanySnapshot::query()->updateOrCreate(
            ['snapshot_month' => $monthDate->toDateString()],
            [
                'total_revenue' => round($totalRevenue, 2),
                'total_payroll' => round($totalPayroll, 2),
                'total_opex' => round($totalOpex, 2),
                'gross_profit' => round($grossProfit, 2),
                'net_profit' => round($netProfit, 2),
                'burn_rate' => round($burnRate, 2),
                'cash_runway_months' => 0,
                'headcount' => $headcount,
                'total_ar' => round($totalAr, 2),
                'created_at' => now(),
            ]
        );
    }

    public function calculateRunway(float $availableCash): float
    {
        $burnRate = $this->calculateBurnRate();
        if ($burnRate <= 0) {
            return 0;
        }

        return round($availableCash / $burnRate, 2);
    }

    private function calculateBurnRate(): float
    {
        $rows = CompanySnapshot::query()
            ->latest('snapshot_month')
            ->limit(3)
            ->get(['total_payroll', 'total_opex']);

        if ($rows->isEmpty()) {
            return 0;
        }

        return $rows->avg(static fn ($row) => (float) $row->total_payroll + (float) $row->total_opex);
    }
}

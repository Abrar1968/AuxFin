<?php

namespace App\Services;

use App\Models\CompanySnapshot;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\ProjectPayment;
use App\Models\SalaryMonth;
use Carbon\Carbon;

class SnapshotService
{
    /**
     * @var array<int, string>
     */
    private const ACCRUAL_INVOICE_STATUSES = ['sent', 'partial', 'paid', 'overdue'];

    public function capture(?string $month = null): CompanySnapshot
    {
        $monthDate = $month
            ? Carbon::parse($month)->startOfMonth()
            : now()->startOfMonth();

        $monthStart = $monthDate->copy()->startOfMonth();
        $monthEnd = $monthDate->copy()->endOfMonth();

        $totalRevenue = (float) Invoice::query()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->whereBetween('invoice_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $totalCashCollected = (float) ProjectPayment::query()
            ->whereBetween('payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
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

        $accruedToDate = (float) Invoice::query()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->whereDate('invoice_date', '<=', $monthEnd->toDateString())
            ->sum('amount');

        $cashCollectedToDate = (float) ProjectPayment::query()
            ->whereDate('payment_date', '<=', $monthEnd->toDateString())
            ->sum('amount');

        $totalAr = max(0, $accruedToDate - $cashCollectedToDate);

        return CompanySnapshot::query()->updateOrCreate(
            ['snapshot_month' => $monthDate->toDateString()],
            [
                'total_revenue' => round($totalRevenue, 2),
                'total_cash_collected' => round($totalCashCollected, 2),
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

<?php

namespace App\Services;

use App\Algorithms\ARHealthScore;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\SalaryMonth;
use App\Models\Setting;
use Carbon\Carbon;

class ReportService
{
    public function profitLoss(?string $fromMonth = null, ?string $toMonth = null): array
    {
        [$from, $to] = $this->resolveRange($fromMonth, $toMonth, 6);
        $taxRate = $this->corporateTaxRate();

        $rows = [];
        $totals = [
            'revenue' => 0.0,
            'payroll' => 0.0,
            'opex' => 0.0,
            'liability_cost' => 0.0,
            'gross_profit' => 0.0,
            'net_profit' => 0.0,
            'estimated_tax' => 0.0,
            'profit_after_tax' => 0.0,
        ];

        foreach ($this->monthsInRange($from, $to) as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = (float) Invoice::query()
                ->whereNotNull('payment_completed_at')
                ->whereBetween('payment_completed_at', [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()])
                ->sum('amount');

            $payroll = (float) SalaryMonth::query()
                ->whereDate('month', $monthStart->toDateString())
                ->sum('net_payable');

            $opex = (float) Expense::query()
                ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $liabilityCost = (float) Liability::query()
                ->whereDate('start_date', '<=', $monthEnd->toDateString())
                ->where(function ($query) use ($monthStart): void {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $monthStart->toDateString());
                })
                ->sum('monthly_payment');

            $grossProfit = $revenue - $payroll;
            $netProfit = $grossProfit - $opex - $liabilityCost;
            $estimatedTax = max(0, $netProfit) * ($taxRate / 100);
            $profitAfterTax = $netProfit - $estimatedTax;

            $row = [
                'month' => $monthStart->format('Y-m'),
                'revenue' => round($revenue, 2),
                'payroll' => round($payroll, 2),
                'opex' => round($opex, 2),
                'liability_cost' => round($liabilityCost, 2),
                'gross_profit' => round($grossProfit, 2),
                'net_profit' => round($netProfit, 2),
                'estimated_tax' => round($estimatedTax, 2),
                'profit_after_tax' => round($profitAfterTax, 2),
            ];

            $rows[] = $row;

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + (float) $row[$key];
            }
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, 2);
        }

        return [
            'from' => $from->format('Y-m'),
            'to' => $to->format('Y-m'),
            'tax_rate_percent' => $taxRate,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function taxSummary(?string $fromMonth = null, ?string $toMonth = null): array
    {
        [$from, $to] = $this->resolveRange($fromMonth, $toMonth, 6);
        $taxRate = $this->corporateTaxRate();

        $rows = [];
        $totals = [
            'taxable_profit' => 0.0,
            'corporate_tax_estimate' => 0.0,
            'payroll_tds_collected' => 0.0,
        ];

        foreach ($this->monthsInRange($from, $to) as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = (float) Invoice::query()
                ->whereNotNull('payment_completed_at')
                ->whereBetween('payment_completed_at', [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()])
                ->sum('amount');

            $payroll = (float) SalaryMonth::query()
                ->whereDate('month', $monthStart->toDateString())
                ->sum('net_payable');

            $opex = (float) Expense::query()
                ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $liabilityCost = (float) Liability::query()
                ->whereDate('start_date', '<=', $monthEnd->toDateString())
                ->where(function ($query) use ($monthStart): void {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $monthStart->toDateString());
                })
                ->sum('monthly_payment');

            $netProfit = $revenue - $payroll - $opex - $liabilityCost;
            $taxableProfit = max(0, $netProfit);
            $corporateTax = $taxableProfit * ($taxRate / 100);

            $payrollTds = (float) SalaryMonth::query()
                ->whereDate('month', $monthStart->toDateString())
                ->sum('tds_deduction');

            $row = [
                'month' => $monthStart->format('Y-m'),
                'taxable_profit' => round($taxableProfit, 2),
                'corporate_tax_estimate' => round($corporateTax, 2),
                'payroll_tds_collected' => round($payrollTds, 2),
            ];

            $rows[] = $row;

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + (float) $row[$key];
            }
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, 2);
        }

        return [
            'from' => $from->format('Y-m'),
            'to' => $to->format('Y-m'),
            'tax_rate_percent' => $taxRate,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function arAging(?string $asOfDate = null): array
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate)->endOfDay() : now()->endOfDay();

        $buckets = [
            '0_30d' => 0.0,
            '31_60d' => 0.0,
            '61_90d' => 0.0,
            '90plus' => 0.0,
        ];

        $items = [];

        $invoices = Invoice::query()
            ->with(['project.client'])
            ->whereNull('payment_completed_at')
            ->orderBy('due_date')
            ->get();

        foreach ($invoices as $invoice) {
            $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
            $age = (int) $dueDate->diffInDays($asOf->copy()->startOfDay(), false);
            $bucket = $this->bucketFromAge($age);

            $outstanding = max(0, (float) $invoice->amount - (float) ($invoice->partial_amount ?? 0));

            $buckets[$bucket] += $outstanding;
            $items[] = [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->project?->client?->name,
                'project_name' => $invoice->project?->name,
                'due_date' => $dueDate->toDateString(),
                'age_days' => max(0, $age),
                'bucket' => $bucket,
                'amount' => round((float) $invoice->amount, 2),
                'partial_amount' => round((float) ($invoice->partial_amount ?? 0), 2),
                'outstanding' => round($outstanding, 2),
            ];
        }

        $total = array_sum($buckets);
        $distribution = [];
        foreach ($buckets as $bucket => $value) {
            $distribution[$bucket] = [
                'amount' => round($value, 2),
                'percent' => $total > 0 ? round(($value / $total) * 100, 2) : 0,
            ];
        }

        return [
            'as_of' => $asOf->toDateString(),
            'total_outstanding' => round($total, 2),
            'distribution' => $distribution,
            'health' => ARHealthScore::calculate(array_map(static fn (array $item): array => [
                'amount' => $item['outstanding'],
                'bucket' => $item['bucket'],
            ], $items)),
            'items' => $items,
        ];
    }

    private function corporateTaxRate(): float
    {
        $policy = Setting::getValue('tax_policy', ['corporate_tax_rate' => 30]);
        if (! is_array($policy)) {
            return 30.0;
        }

        return (float) ($policy['corporate_tax_rate'] ?? 30);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(?string $fromMonth, ?string $toMonth, int $defaultMonths = 6): array
    {
        $to = $toMonth ? Carbon::parse($toMonth)->startOfMonth() : now()->startOfMonth();
        $from = $fromMonth ? Carbon::parse($fromMonth)->startOfMonth() : $to->copy()->subMonths(max(1, $defaultMonths) - 1);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @return array<int, Carbon>
     */
    private function monthsInRange(Carbon $from, Carbon $to): array
    {
        $months = [];
        $cursor = $from->copy();

        while ($cursor->lessThanOrEqualTo($to)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        return $months;
    }

    private function bucketFromAge(int $age): string
    {
        if ($age <= 30) {
            return '0_30d';
        }

        if ($age <= 60) {
            return '31_60d';
        }

        if ($age <= 90) {
            return '61_90d';
        }

        return '90plus';
    }
}

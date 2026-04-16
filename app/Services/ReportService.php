<?php

namespace App\Services;

use App\Algorithms\ARHealthScore;
use App\Models\Asset;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\ProjectPayment;
use App\Models\SalaryMonth;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @var array<int, string>
     */
    private const ACCRUAL_INVOICE_STATUSES = ['sent', 'partial', 'paid', 'overdue'];

    public function profitLoss(?string $fromMonth = null, ?string $toMonth = null): array
    {
        [$from, $to] = $this->resolveRange($fromMonth, $toMonth, 6);
        $taxRate = $this->corporateTaxRate();

        $rows = [];
        $totals = [
            'revenue' => 0.0,
            'cash_collected' => 0.0,
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
                ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
                ->whereBetween('invoice_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $cashCollected = (float) ProjectPayment::query()
                ->whereBetween('payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
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
                'cash_collected' => round($cashCollected, 2),
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
                ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
                ->whereBetween('invoice_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
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
            ->withSum('payments as paid_amount', 'amount')
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->orderBy('due_date')
            ->get();

        foreach ($invoices as $invoice) {
            $paidAmount = (float) ($invoice->paid_amount ?? 0);
            $outstanding = max(0, (float) $invoice->amount - $paidAmount);

            if ($outstanding <= 0) {
                continue;
            }

            $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
            $age = (int) $dueDate->diffInDays($asOf->copy()->startOfDay(), false);
            $bucket = $this->bucketFromAge($age);

            $buckets[$bucket] += $outstanding;
            $items[] = [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->project?->client?->name,
                'project_name' => $invoice->project?->name,
                'invoice_date' => optional($invoice->invoice_date)->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'age_days' => max(0, $age),
                'bucket' => $bucket,
                'amount' => round((float) $invoice->amount, 2),
                'partial_amount' => round($paidAmount, 2),
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

    public function trialBalance(?string $asOfDate = null): array
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate)->endOfDay() : now()->endOfDay();
        $balanceSheet = $this->balanceSheet($asOf->toDateString());

        $lines = [
            [
                'account_code' => '1100',
                'account_name' => 'Cash and Bank',
                'debit' => (float) ($balanceSheet['assets']['cash_and_bank'] ?? 0),
                'credit' => 0.0,
            ],
            [
                'account_code' => '1200',
                'account_name' => 'Accounts Receivable',
                'debit' => (float) ($balanceSheet['assets']['accounts_receivable'] ?? 0),
                'credit' => 0.0,
            ],
            [
                'account_code' => '1500',
                'account_name' => 'Fixed Assets',
                'debit' => (float) ($balanceSheet['assets']['fixed_assets'] ?? 0),
                'credit' => 0.0,
            ],
            [
                'account_code' => '2100',
                'account_name' => 'Liabilities Outstanding',
                'debit' => 0.0,
                'credit' => (float) ($balanceSheet['liabilities']['outstanding_liabilities'] ?? 0),
            ],
            [
                'account_code' => '2200',
                'account_name' => 'Bank Overdraft',
                'debit' => 0.0,
                'credit' => (float) ($balanceSheet['liabilities']['bank_overdraft'] ?? 0),
            ],
        ];

        $equity = (float) ($balanceSheet['equity']['retained_earnings'] ?? 0);
        if ($equity >= 0) {
            $lines[] = [
                'account_code' => '3100',
                'account_name' => 'Retained Earnings',
                'debit' => 0.0,
                'credit' => $equity,
            ];
        } else {
            $lines[] = [
                'account_code' => '3100',
                'account_name' => 'Retained Earnings (Deficit)',
                'debit' => abs($equity),
                'credit' => 0.0,
            ];
        }

        $normalizedLines = collect($lines)
            ->map(static fn (array $line): array => [
                'account_code' => $line['account_code'],
                'account_name' => $line['account_name'],
                'debit' => round((float) $line['debit'], 2),
                'credit' => round((float) $line['credit'], 2),
            ])
            ->filter(static fn (array $line): bool => $line['debit'] > 0 || $line['credit'] > 0)
            ->values()
            ->all();

        $totalDebit = round((float) collect($normalizedLines)->sum('debit'), 2);
        $totalCredit = round((float) collect($normalizedLines)->sum('credit'), 2);

        return [
            'as_of' => $asOf->toDateString(),
            'lines' => $normalizedLines,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'difference' => round($totalDebit - $totalCredit, 2),
            ],
            'is_balanced' => abs($totalDebit - $totalCredit) <= 0.01,
        ];
    }

    public function balanceSheet(?string $asOfDate = null): array
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate)->endOfDay() : now()->endOfDay();
        $asOfDateString = $asOf->toDateString();

        $accruedRevenue = (float) Invoice::query()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->whereDate('invoice_date', '<=', $asOfDateString)
            ->sum('amount');

        $cashCollected = (float) ProjectPayment::query()
            ->whereDate('payment_date', '<=', $asOfDateString)
            ->sum('amount');

        $payrollPaid = (float) SalaryMonth::query()
            ->whereDate('month', '<=', $asOf->copy()->startOfMonth()->toDateString())
            ->sum('net_payable');

        $opexPaid = (float) Expense::query()
            ->whereDate('expense_date', '<=', $asOfDateString)
            ->sum('amount');

        $liabilityPrincipalPaid = $this->liabilityPrincipalPaidAsOf($asOf);
        $netCashPosition = $cashCollected - $payrollPaid - $opexPaid - $liabilityPrincipalPaid;

        $cashAndBank = max(0, $netCashPosition);
        $bankOverdraft = max(0, -$netCashPosition);
        $accountsReceivable = max(0, $accruedRevenue - $cashCollected);

        $fixedAssets = (float) Asset::query()
            ->whereDate('purchase_date', '<=', $asOfDateString)
            ->sum('current_book_value');

        $outstandingLiabilities = (float) Liability::query()
            ->whereDate('start_date', '<=', $asOfDateString)
            ->where('outstanding', '>', 0)
            ->sum('outstanding');

        $totalAssets = $cashAndBank + $accountsReceivable + $fixedAssets;
        $totalLiabilities = $outstandingLiabilities + $bankOverdraft;
        $retainedEarnings = $totalAssets - $totalLiabilities;

        return [
            'as_of' => $asOfDateString,
            'assets' => [
                'cash_and_bank' => round($cashAndBank, 2),
                'accounts_receivable' => round($accountsReceivable, 2),
                'fixed_assets' => round($fixedAssets, 2),
            ],
            'liabilities' => [
                'outstanding_liabilities' => round($outstandingLiabilities, 2),
                'bank_overdraft' => round($bankOverdraft, 2),
            ],
            'equity' => [
                'retained_earnings' => round($retainedEarnings, 2),
            ],
            'totals' => [
                'assets' => round($totalAssets, 2),
                'liabilities' => round($totalLiabilities, 2),
                'equity' => round($retainedEarnings, 2),
                'liabilities_plus_equity' => round($totalLiabilities + $retainedEarnings, 2),
            ],
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $retainedEarnings)) <= 0.01,
        ];
    }

    public function cashFlow(?string $fromMonth = null, ?string $toMonth = null): array
    {
        [$from, $to] = $this->resolveRange($fromMonth, $toMonth, 6);

        $rows = [];
        $openingBalance = 0.0;
        $totals = [
            'cash_in_collections' => 0.0,
            'operating_outflow' => 0.0,
            'financing_outflow' => 0.0,
            'net_cash_flow' => 0.0,
        ];

        foreach ($this->monthsInRange($from, $to) as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $cashInCollections = (float) ProjectPayment::query()
                ->whereBetween('payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $payrollOutflow = (float) SalaryMonth::query()
                ->whereDate('month', $monthStart->toDateString())
                ->sum('net_payable');

            $opexOutflow = (float) Expense::query()
                ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $financingOutflow = $this->liabilityMonthlyCostForMonth($monthStart, $monthEnd);

            $operatingOutflow = $payrollOutflow + $opexOutflow;
            $netCashFlow = $cashInCollections - $operatingOutflow - $financingOutflow;
            $closingBalance = $openingBalance + $netCashFlow;

            $row = [
                'month' => $monthStart->format('Y-m'),
                'opening_balance' => round($openingBalance, 2),
                'cash_in_collections' => round($cashInCollections, 2),
                'operating_outflow' => round($operatingOutflow, 2),
                'financing_outflow' => round($financingOutflow, 2),
                'net_cash_flow' => round($netCashFlow, 2),
                'closing_balance' => round($closingBalance, 2),
            ];

            $rows[] = $row;

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + (float) $row[$key];
            }

            $openingBalance = $closingBalance;
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, 2);
        }

        $totals['opening_balance'] = round($rows[0]['opening_balance'] ?? 0, 2);
        $totals['ending_balance'] = round($openingBalance, 2);

        return [
            'from' => $from->format('Y-m'),
            'to' => $to->format('Y-m'),
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function generalLedger(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $projectId = null,
        ?int $invoiceId = null,
        int $perPage = 50,
        int $page = 1,
    ): array {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate, 90);

        $invoiceEntries = Invoice::query()
            ->with(['project.client'])
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->whereNotNull('invoice_date')
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($invoiceId, fn ($query) => $query->where('id', $invoiceId))
            ->get()
            ->map(static function (Invoice $invoice): array {
                $entryDate = optional($invoice->invoice_date)->toDateString() ?? optional($invoice->created_at)->toDateString();

                return [
                    'entry_date' => $entryDate,
                    'entry_type' => 'invoice_accrual',
                    'reference' => 'INV-'.$invoice->id,
                    'description' => 'Invoice accrued: '.$invoice->invoice_number,
                    'project_id' => $invoice->project_id,
                    'project_name' => $invoice->project?->name,
                    'client_name' => $invoice->project?->client?->name,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'debit_account' => 'Accounts Receivable',
                    'credit_account' => 'Revenue',
                    'amount' => round((float) $invoice->amount, 2),
                    'sort_weight' => 1,
                    'sort_id' => $invoice->id,
                ];
            });

        $paymentEntries = ProjectPayment::query()
            ->with(['project.client', 'invoice'])
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($invoiceId, fn ($query) => $query->where('invoice_id', $invoiceId))
            ->get()
            ->map(static function (ProjectPayment $payment): array {
                return [
                    'entry_date' => optional($payment->payment_date)->toDateString(),
                    'entry_type' => 'payment_collection',
                    'reference' => 'PMT-'.$payment->id,
                    'description' => 'Payment received'.($payment->reference_number ? ' ('.$payment->reference_number.')' : ''),
                    'project_id' => $payment->project_id,
                    'project_name' => $payment->project?->name,
                    'client_name' => $payment->project?->client?->name,
                    'invoice_id' => $payment->invoice_id,
                    'invoice_number' => $payment->invoice?->invoice_number,
                    'debit_account' => 'Cash and Bank',
                    'credit_account' => 'Accounts Receivable',
                    'amount' => round((float) $payment->amount, 2),
                    'payment_method' => $payment->payment_method,
                    'sort_weight' => 2,
                    'sort_id' => $payment->id,
                ];
            });

        $entries = $invoiceEntries
            ->concat($paymentEntries)
            ->sort(static function (array $a, array $b): int {
                $left = [$a['entry_date'] ?? '', $a['sort_weight'] ?? 0, $a['sort_id'] ?? 0];
                $right = [$b['entry_date'] ?? '', $b['sort_weight'] ?? 0, $b['sort_id'] ?? 0];

                return $left <=> $right;
            })
            ->values();

        $totalEntries = $entries->count();
        $perPage = max(1, min(200, $perPage));
        $lastPage = max(1, (int) ceil($totalEntries / $perPage));
        $page = max(1, min($page, $lastPage));

        $pagedEntries = $entries
            ->forPage($page, $perPage)
            ->map(static function (array $entry): array {
                unset($entry['sort_weight'], $entry['sort_id']);

                return $entry;
            })
            ->values()
            ->all();

        $totalAmount = round((float) $entries->sum('amount'), 2);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'entries' => $pagedEntries,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalEntries,
                'last_page' => $lastPage,
            ],
            'summary' => [
                'total_debit' => $totalAmount,
                'total_credit' => $totalAmount,
            ],
        ];
    }

    public function paymentLedger(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $projectId = null,
        ?int $invoiceId = null,
        int $perPage = 50,
        int $page = 1,
    ): array {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate, 90);

        $baseQuery = ProjectPayment::query()
            ->with(['project.client', 'invoice', 'recorder'])
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($invoiceId, fn ($query) => $query->where('invoice_id', $invoiceId));

        $totalCount = (clone $baseQuery)->count();
        $totalAmount = (float) (clone $baseQuery)->sum('amount');

        $perPage = max(1, min(200, $perPage));
        $lastPage = max(1, (int) ceil($totalCount / $perPage));
        $page = max(1, min($page, $lastPage));

        $rows = (clone $baseQuery)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        $methodTotals = (clone $baseQuery)
            ->get(['payment_method', 'amount'])
            ->groupBy('payment_method')
            ->map(static fn (Collection $group): float => round((float) $group->sum('amount'), 2))
            ->sortKeys()
            ->all();

        $entries = $rows->map(static function (ProjectPayment $payment): array {
            return [
                'payment_id' => $payment->id,
                'payment_date' => optional($payment->payment_date)->toDateString(),
                'project_id' => $payment->project_id,
                'project_name' => $payment->project?->name,
                'client_name' => $payment->project?->client?->name,
                'invoice_id' => $payment->invoice_id,
                'invoice_number' => $payment->invoice?->invoice_number,
                'amount' => round((float) $payment->amount, 2),
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'notes' => $payment->notes,
                'recorded_by' => $payment->recorder?->name,
            ];
        })->values()->all();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'entries' => $entries,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalCount,
                'last_page' => $lastPage,
            ],
            'summary' => [
                'total_amount' => round($totalAmount, 2),
                'payment_count' => $totalCount,
                'by_payment_method' => $methodTotals,
            ],
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
    private function resolveDateRange(?string $fromDate, ?string $toDate, int $defaultDays = 90): array
    {
        $to = $toDate ? Carbon::parse($toDate)->endOfDay() : now()->endOfDay();
        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : $to->copy()->subDays(max(1, $defaultDays) - 1)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function liabilityPrincipalPaidAsOf(Carbon $asOf): float
    {
        return (float) Liability::query()
            ->whereDate('start_date', '<=', $asOf->toDateString())
            ->get(['principal_amount', 'outstanding'])
            ->sum(static function (Liability $liability): float {
                return max(0, (float) $liability->principal_amount - (float) $liability->outstanding);
            });
    }

    private function liabilityMonthlyCostForMonth(Carbon $monthStart, Carbon $monthEnd): float
    {
        return (float) Liability::query()
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->where(function ($query) use ($monthStart): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $monthStart->toDateString());
            })
            ->sum('monthly_payment');
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

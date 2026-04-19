<?php

namespace App\Services;

use App\Algorithms\ARHealthScore;
use App\Models\Asset;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\OwnerEquityEntry;
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

    /**
     * @var array<int, string>
     */
    private const PAYROLL_ACCRUAL_STATUSES = ['processed', 'paid'];

    /**
     * @var array<string, string>
     */
    private const ACCOUNT_NAMES = [
        '1100' => 'Cash and Bank',
        '1200' => 'Accounts Receivable',
        '1250' => 'Prepaid Expenses',
        '1500' => 'Fixed Assets (Gross)',
        '1590' => 'Accumulated Depreciation',
        '2100' => 'Outstanding Liabilities',
        '2150' => 'Accounts Payable',
        '2200' => 'Bank Overdraft',
        '2300' => 'Unearned Revenue',
        '2400' => 'Salary Payable',
        '2450' => 'Payroll Tax Payable',
        '2460' => 'Payroll Recoveries Reserve',
        '3100' => 'Retained Earnings / Opening Equity',
        '3200' => 'Owner Capital Contributions',
        '3300' => 'Owner Drawings',
        '4100' => 'Service Revenue',
        '5100' => 'Payroll Expense',
        '5200' => 'Operating Expense',
        '5300' => 'Depreciation Expense',
        '5400' => 'Liability Finance Cost',
    ];

    public function profitLoss(?string $fromMonth = null, ?string $toMonth = null): array
    {
        [$from, $to] = $this->resolveRange($fromMonth, $toMonth, 6);
        $taxRate = $this->corporateTaxRate();
        $depreciationSeries = $this->depreciationSeries($to->copy()->endOfMonth());

        $rows = [];
        $totals = [
            'revenue' => 0.0,
            'cash_collected' => 0.0,
            'payroll' => 0.0,
            'opex' => 0.0,
            'depreciation' => 0.0,
            'liability_cost' => 0.0,
            'gross_profit' => 0.0,
            'net_profit' => 0.0,
            'estimated_tax' => 0.0,
            'profit_after_tax' => 0.0,
        ];

        foreach ($this->monthsInRange($from, $to) as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = $this->accruedRevenueForPeriod($monthStart, $monthEnd);
            $cashCollected = (float) ProjectPayment::query()
                ->whereBetween('payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $payrollComponents = $this->payrollAccruedComponentsForMonth($monthStart);
            $payroll = (float) $payrollComponents['gross'];

            $opexBase = $this->accruedOperatingExpenseForPeriod($monthStart, $monthEnd);
            $depreciation = (float) ($depreciationSeries[$monthStart->format('Y-m')] ?? 0.0);
            $opex = $opexBase + $depreciation;

            $liabilityCost = $this->liabilityMonthlyCostForMonth($monthStart, $monthEnd);

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
                'depreciation' => round($depreciation, 2),
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
        $depreciationSeries = $this->depreciationSeries($to->copy()->endOfMonth());

        $rows = [];
        $totals = [
            'taxable_profit' => 0.0,
            'corporate_tax_estimate' => 0.0,
            'payroll_tds_collected' => 0.0,
        ];

        foreach ($this->monthsInRange($from, $to) as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = $this->accruedRevenueForPeriod($monthStart, $monthEnd);
            $payrollComponents = $this->payrollAccruedComponentsForMonth($monthStart);
            $payroll = (float) $payrollComponents['gross'];
            $payrollTds = (float) $payrollComponents['tds'];
            $opex = $this->accruedOperatingExpenseForPeriod($monthStart, $monthEnd) + (float) ($depreciationSeries[$monthStart->format('Y-m')] ?? 0.0);
            $liabilityCost = $this->liabilityMonthlyCostForMonth($monthStart, $monthEnd);

            $netProfit = $revenue - $payroll - $opex - $liabilityCost;
            $taxableProfit = max(0, $netProfit);
            $corporateTax = $taxableProfit * ($taxRate / 100);

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
        $asOfDateString = $asOf->toDateString();

        $buckets = [
            '0_30d' => 0.0,
            '31_60d' => 0.0,
            '61_90d' => 0.0,
            '90plus' => 0.0,
        ];

        $items = [];

        $invoices = Invoice::query()
            ->with(['project.client'])
            ->withSum([
                'payments as paid_amount' => fn ($query) => $query->whereDate('payment_date', '<=', $asOfDateString),
            ], 'amount')
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->whereDate('invoice_date', '<=', $asOfDateString)
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
        $asOfDateString = $asOf->toDateString();
        $asOfMonthStart = $asOf->copy()->startOfMonth();

        $accruedRevenue = $this->accruedRevenueAsOf($asOfDateString);
        $linkedCollections = $this->linkedCollectionsAsOf($asOfDateString);
        $unearnedRevenue = $this->unearnedCollectionsAsOf($asOfDateString);
        $accountsReceivable = max(0, $accruedRevenue - $linkedCollections);

        $assetPurchases = $this->assetPurchasesAsOf($asOfDateString);
        $prepaidExpenses = $this->prepaidUnamortizedAsOf($asOfDateString);
        $accumulatedDepreciation = $this->accumulatedDepreciationAsOf($asOfDateString);
        $operatingExpense = $this->accruedOperatingExpenseAsOf($asOfDateString);

        $accountsPayable = $this->accountsPayableAsOf($asOfDateString);
        $ownerCapital = $this->ownerCapitalAsOf($asOfDateString);
        $ownerDrawings = $this->ownerDrawingsAsOf($asOfDateString);

        $payrollAsOf = $this->payrollAccruedComponentsAsOf($asOfMonthStart);
        $salaryPayable = $this->salaryPayableAsOf($asOfMonthStart, (float) $payrollAsOf['net']);
        $payrollTaxPayable = (float) $payrollAsOf['tax'];
        $payrollRecoveries = (float) $payrollAsOf['recoveries'];

        $liabilityCostAsOf = $this->liabilityCostAsOf($asOf);
        $outstandingLiabilities = (float) Liability::query()
            ->whereDate('start_date', '<=', $asOfDateString)
            ->where('outstanding', '>', 0)
            ->sum('outstanding');

        $cashPosition = $this->cashPositionAsOf($asOf);
        $cashAndBank = max(0, $cashPosition);
        $bankOverdraft = max(0, -$cashPosition);

        $lines = collect([
            [
                'account_code' => '1100',
                'account_name' => $this->accountName('1100'),
                'debit' => $cashAndBank,
                'credit' => 0.0,
            ],
            [
                'account_code' => '1200',
                'account_name' => $this->accountName('1200'),
                'debit' => $accountsReceivable,
                'credit' => 0.0,
            ],
            [
                'account_code' => '1500',
                'account_name' => $this->accountName('1500'),
                'debit' => $assetPurchases,
                'credit' => 0.0,
            ],
            [
                'account_code' => '1250',
                'account_name' => $this->accountName('1250'),
                'debit' => $prepaidExpenses,
                'credit' => 0.0,
            ],
            [
                'account_code' => '1590',
                'account_name' => $this->accountName('1590'),
                'debit' => 0.0,
                'credit' => $accumulatedDepreciation,
            ],
            [
                'account_code' => '2100',
                'account_name' => $this->accountName('2100'),
                'debit' => 0.0,
                'credit' => $outstandingLiabilities,
            ],
            [
                'account_code' => '2200',
                'account_name' => $this->accountName('2200'),
                'debit' => 0.0,
                'credit' => $bankOverdraft,
            ],
            [
                'account_code' => '2150',
                'account_name' => $this->accountName('2150'),
                'debit' => 0.0,
                'credit' => $accountsPayable,
            ],
            [
                'account_code' => '2300',
                'account_name' => $this->accountName('2300'),
                'debit' => 0.0,
                'credit' => $unearnedRevenue,
            ],
            [
                'account_code' => '2400',
                'account_name' => $this->accountName('2400'),
                'debit' => 0.0,
                'credit' => $salaryPayable,
            ],
            [
                'account_code' => '2450',
                'account_name' => $this->accountName('2450'),
                'debit' => 0.0,
                'credit' => $payrollTaxPayable,
            ],
            [
                'account_code' => '2460',
                'account_name' => $this->accountName('2460'),
                'debit' => 0.0,
                'credit' => $payrollRecoveries,
            ],
            [
                'account_code' => '4100',
                'account_name' => $this->accountName('4100'),
                'debit' => 0.0,
                'credit' => $accruedRevenue,
            ],
            [
                'account_code' => '5100',
                'account_name' => $this->accountName('5100'),
                'debit' => (float) $payrollAsOf['gross'],
                'credit' => 0.0,
            ],
            [
                'account_code' => '5200',
                'account_name' => $this->accountName('5200'),
                'debit' => $operatingExpense,
                'credit' => 0.0,
            ],
            [
                'account_code' => '5300',
                'account_name' => $this->accountName('5300'),
                'debit' => $accumulatedDepreciation,
                'credit' => 0.0,
            ],
            [
                'account_code' => '5400',
                'account_name' => $this->accountName('5400'),
                'debit' => $liabilityCostAsOf,
                'credit' => 0.0,
            ],
            [
                'account_code' => '3200',
                'account_name' => $this->accountName('3200'),
                'debit' => 0.0,
                'credit' => $ownerCapital,
            ],
            [
                'account_code' => '3300',
                'account_name' => $this->accountName('3300'),
                'debit' => $ownerDrawings,
                'credit' => 0.0,
            ],
        ])->map(static fn (array $line): array => [
            'account_code' => $line['account_code'],
            'account_name' => $line['account_name'],
            'debit' => round(max(0, (float) $line['debit']), 2),
            'credit' => round(max(0, (float) $line['credit']), 2),
        ])->filter(static fn (array $line): bool => $line['debit'] > 0 || $line['credit'] > 0)->values();

        $preDebit = round((float) $lines->sum('debit'), 2);
        $preCredit = round((float) $lines->sum('credit'), 2);
        $difference = round($preDebit - $preCredit, 2);

        if ($difference > 0.01) {
            $lines->push([
                'account_code' => '3100',
                'account_name' => $this->accountName('3100'),
                'debit' => 0.0,
                'credit' => round($difference, 2),
            ]);
        } elseif ($difference < -0.01) {
            $lines->push([
                'account_code' => '3100',
                'account_name' => $this->accountName('3100'),
                'debit' => round(abs($difference), 2),
                'credit' => 0.0,
            ]);
        }

        $normalizedLines = $lines->values()->all();
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
        $asOfMonthStart = $asOf->copy()->startOfMonth();

        $accruedRevenue = $this->accruedRevenueAsOf($asOfDateString);
        $linkedCollections = $this->linkedCollectionsAsOf($asOfDateString);
        $unearnedRevenue = $this->unearnedCollectionsAsOf($asOfDateString);

        $accountsReceivable = max(0, $accruedRevenue - $linkedCollections);
        $assetPurchases = $this->assetPurchasesAsOf($asOfDateString);
        $accumulatedDepreciation = $this->accumulatedDepreciationAsOf($asOfDateString);
        $fixedAssetsNet = max(0, $assetPurchases - $accumulatedDepreciation);
        $prepaidExpenses = $this->prepaidUnamortizedAsOf($asOfDateString);
        $accountsPayable = $this->accountsPayableAsOf($asOfDateString);

        $payrollAsOf = $this->payrollAccruedComponentsAsOf($asOfMonthStart);
        $salaryPayable = $this->salaryPayableAsOf($asOfMonthStart, (float) $payrollAsOf['net']);
        $payrollTaxPayable = (float) $payrollAsOf['tax'];
        $payrollRecoveries = (float) $payrollAsOf['recoveries'];

        $ownerCapital = $this->ownerCapitalAsOf($asOfDateString);
        $ownerDrawings = $this->ownerDrawingsAsOf($asOfDateString);

        $liabilitiesOutstanding = (float) Liability::query()
            ->whereDate('start_date', '<=', $asOfDateString)
            ->where('outstanding', '>', 0)
            ->sum('outstanding');

        $cashPosition = $this->cashPositionAsOf($asOf);
        $cashAndBank = max(0, $cashPosition);
        $bankOverdraft = max(0, -$cashPosition);

        $totalAssets = $cashAndBank + $accountsReceivable + $fixedAssetsNet + $prepaidExpenses;

        $outstandingLiabilityTotal = $liabilitiesOutstanding + $accountsPayable + $unearnedRevenue + $salaryPayable + $payrollTaxPayable + $payrollRecoveries;
        $totalLiabilities = $outstandingLiabilityTotal + $bankOverdraft;
        $retainedEarnings = $totalAssets - $totalLiabilities - $ownerCapital + $ownerDrawings;
        $totalEquity = $ownerCapital + $retainedEarnings - $ownerDrawings;

        return [
            'as_of' => $asOfDateString,
            'assets' => [
                'cash_and_bank' => round($cashAndBank, 2),
                'accounts_receivable' => round($accountsReceivable, 2),
                'fixed_assets' => round($fixedAssetsNet, 2),
                'fixed_assets_gross' => round($assetPurchases, 2),
                'accumulated_depreciation' => round($accumulatedDepreciation, 2),
                'prepaid_expenses' => round($prepaidExpenses, 2),
            ],
            'liabilities' => [
                'outstanding_liabilities' => round($outstandingLiabilityTotal, 2),
                'bank_overdraft' => round($bankOverdraft, 2),
                'accounts_payable' => round($accountsPayable, 2),
                'unearned_revenue' => round($unearnedRevenue, 2),
                'salary_payable' => round($salaryPayable, 2),
                'payroll_tax_payable' => round($payrollTaxPayable, 2),
                'payroll_recoveries_reserve' => round($payrollRecoveries, 2),
            ],
            'equity' => [
                'owner_capital' => round($ownerCapital, 2),
                'owner_drawings' => round($ownerDrawings, 2),
                'retained_earnings' => round($retainedEarnings, 2),
            ],
            'totals' => [
                'assets' => round($totalAssets, 2),
                'liabilities' => round($totalLiabilities, 2),
                'equity' => round($totalEquity, 2),
                'liabilities_plus_equity' => round($totalLiabilities + $totalEquity, 2),
            ],
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) <= 0.01,
        ];
    }

    public function cashFlow(?string $fromMonth = null, ?string $toMonth = null): array
    {
        [$from, $to] = $this->resolveRange($fromMonth, $toMonth, 6);

        $rows = [];
        $openingBalance = round($this->cashPositionAsOf($from->copy()->subDay()->endOfDay()), 2);
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

            $payrollPaid = $this->payrollPaidForPeriod($monthStart, $monthEnd);
            $opexOutflow = $this->expenseCashPaidForPeriod($monthStart, $monthEnd);
            $operatingOutflow = $payrollPaid + $opexOutflow;

            $assetPurchaseOutflow = $this->assetPurchasesForPeriod($monthStart, $monthEnd);
            $liabilityOutflow = $this->liabilityMonthlyCostForMonth($monthStart, $monthEnd);
            $financingOutflow = $assetPurchaseOutflow + $liabilityOutflow;

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

        $entries = $this->buildGeneralLedgerRows($from, $to, $projectId, $invoiceId)
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

    private function buildGeneralLedgerRows(
        Carbon $from,
        Carbon $to,
        ?int $projectId,
        ?int $invoiceId,
    ): Collection {
        $rows = collect();
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $fromMonth = $from->copy()->startOfMonth()->toDateString();
        $toMonth = $to->copy()->startOfMonth()->toDateString();

        $invoiceEntries = Invoice::query()
            ->with(['project.client'])
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->whereNotNull('invoice_date')
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($invoiceId, fn ($query) => $query->where('id', $invoiceId))
            ->get();

        foreach ($invoiceEntries as $invoice) {
            $this->appendLedgerRow(
                $rows,
                entryDate: optional($invoice->invoice_date)->toDateString() ?? optional($invoice->created_at)->toDateString(),
                entryType: 'invoice_accrual',
                reference: 'INV-'.$invoice->id,
                description: 'Invoice accrued: '.$invoice->invoice_number,
                projectId: $invoice->project_id,
                projectName: $invoice->project?->name,
                clientName: $invoice->project?->client?->name,
                invoiceId: $invoice->id,
                invoiceNumber: $invoice->invoice_number,
                debitAccount: $this->accountName('1200'),
                creditAccount: $this->accountName('4100'),
                amount: (float) $invoice->amount,
                sortWeight: 10,
                sortId: (int) $invoice->id,
            );
        }

        $paymentEntries = ProjectPayment::query()
            ->with(['project.client', 'invoice'])
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($invoiceId, fn ($query) => $query->where('invoice_id', $invoiceId))
            ->get();

        foreach ($paymentEntries as $payment) {
            $this->appendLedgerRow(
                $rows,
                entryDate: optional($payment->payment_date)->toDateString(),
                entryType: 'payment_collection',
                reference: 'PMT-'.$payment->id,
                description: 'Payment received'.($payment->reference_number ? ' ('.$payment->reference_number.')' : ''),
                projectId: $payment->project_id,
                projectName: $payment->project?->name,
                clientName: $payment->project?->client?->name,
                invoiceId: $payment->invoice_id,
                invoiceNumber: $payment->invoice?->invoice_number,
                debitAccount: $this->accountName('1100'),
                creditAccount: $payment->invoice_id ? $this->accountName('1200') : $this->accountName('2300'),
                amount: (float) $payment->amount,
                sortWeight: 20,
                sortId: (int) $payment->id,
                extra: [
                    'payment_method' => $payment->payment_method,
                ],
            );
        }

        if ($projectId || $invoiceId) {
            return $rows;
        }

        $expenseEntries = Expense::query()
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->get();

        foreach ($expenseEntries as $expense) {
            $mode = (string) ($expense->accounting_mode ?? 'cash');

            if ($mode === 'prepaid') {
                $this->appendLedgerRow(
                    $rows,
                    entryDate: optional($expense->expense_date)->toDateString(),
                    entryType: 'prepaid_expense_purchase',
                    reference: 'EXP-'.$expense->id,
                    description: 'Prepaid expense booked: '.$expense->category,
                    projectId: null,
                    projectName: null,
                    clientName: null,
                    invoiceId: null,
                    invoiceNumber: null,
                    debitAccount: $this->accountName('1250'),
                    creditAccount: $this->accountName('1100'),
                    amount: (float) $expense->amount,
                    sortWeight: 30,
                    sortId: (int) $expense->id,
                );

                continue;
            }

            $this->appendLedgerRow(
                $rows,
                entryDate: optional($expense->expense_date)->toDateString(),
                entryType: 'expense_recorded',
                reference: 'EXP-'.$expense->id,
                description: 'Expense booked: '.$expense->category,
                projectId: null,
                projectName: null,
                clientName: null,
                invoiceId: null,
                invoiceNumber: null,
                debitAccount: $this->accountName('5200'),
                creditAccount: $mode === 'payable' ? $this->accountName('2150') : $this->accountName('1100'),
                amount: (float) $expense->amount,
                sortWeight: 30,
                sortId: (int) $expense->id,
            );
        }

        $expenseSettlementEntries = ExpensePayment::query()
            ->with('expense')
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->whereHas('expense', fn ($query) => $query->where('accounting_mode', 'payable'))
            ->get();

        foreach ($expenseSettlementEntries as $payment) {
            $expense = $payment->expense;
            if (! $expense) {
                continue;
            }

            $this->appendLedgerRow(
                $rows,
                entryDate: optional($payment->payment_date)->toDateString(),
                entryType: 'expense_payment',
                reference: 'EXP-PAY-'.$payment->id,
                description: 'Expense payable settled'.($payment->reference_number ? ' ('.$payment->reference_number.')' : ''),
                projectId: null,
                projectName: null,
                clientName: null,
                invoiceId: null,
                invoiceNumber: null,
                debitAccount: $this->accountName('2150'),
                creditAccount: $this->accountName('1100'),
                amount: (float) $payment->amount,
                sortWeight: 35,
                sortId: (int) $payment->id,
            );
        }

        $salaryAccruals = SalaryMonth::query()
            ->whereIn('status', self::PAYROLL_ACCRUAL_STATUSES)
            ->whereDate('month', '>=', $fromMonth)
            ->whereDate('month', '<=', $toMonth)
            ->get();

        foreach ($salaryAccruals as $salaryMonth) {
            $gross = round((float) $salaryMonth->gross_earnings, 2);
            $tax = round((float) $salaryMonth->tds_deduction + (float) $salaryMonth->pf_deduction + (float) $salaryMonth->professional_tax, 2);
            $recoveries = round((float) $salaryMonth->unpaid_leave_deduction + (float) $salaryMonth->late_penalty_deduction + (float) $salaryMonth->loan_emi_deduction, 2);
            $net = round((float) $salaryMonth->net_payable, 2);

            $allocated = round($net + $tax + $recoveries, 2);
            $diff = round($gross - $allocated, 2);
            if (abs($diff) > 0.01) {
                $net = round($net + $diff, 2);
            }

            $entryDate = optional($salaryMonth->month)->toDateString();
            $reference = 'SAL-ACR-'.$salaryMonth->id;
            $monthLabel = optional($salaryMonth->month)->format('Y-m') ?? 'n/a';

            if ($net > 0) {
                $this->appendLedgerRow(
                    $rows,
                    entryDate: $entryDate,
                    entryType: 'payroll_accrual',
                    reference: $reference,
                    description: 'Payroll accrued for '.$monthLabel.' (net payable)',
                    projectId: null,
                    projectName: null,
                    clientName: null,
                    invoiceId: null,
                    invoiceNumber: null,
                    debitAccount: $this->accountName('5100'),
                    creditAccount: $this->accountName('2400'),
                    amount: $net,
                    sortWeight: 40,
                    sortId: (int) $salaryMonth->id,
                );
            }

            if ($tax > 0) {
                $this->appendLedgerRow(
                    $rows,
                    entryDate: $entryDate,
                    entryType: 'payroll_accrual',
                    reference: $reference,
                    description: 'Payroll accrued for '.$monthLabel.' (tax withholding)',
                    projectId: null,
                    projectName: null,
                    clientName: null,
                    invoiceId: null,
                    invoiceNumber: null,
                    debitAccount: $this->accountName('5100'),
                    creditAccount: $this->accountName('2450'),
                    amount: $tax,
                    sortWeight: 40,
                    sortId: (int) $salaryMonth->id,
                );
            }

            if ($recoveries > 0) {
                $this->appendLedgerRow(
                    $rows,
                    entryDate: $entryDate,
                    entryType: 'payroll_accrual',
                    reference: $reference,
                    description: 'Payroll accrued for '.$monthLabel.' (other recoveries)',
                    projectId: null,
                    projectName: null,
                    clientName: null,
                    invoiceId: null,
                    invoiceNumber: null,
                    debitAccount: $this->accountName('5100'),
                    creditAccount: $this->accountName('2460'),
                    amount: $recoveries,
                    sortWeight: 40,
                    sortId: (int) $salaryMonth->id,
                );
            }
        }

        $salaryPaidEntries = SalaryMonth::query()
            ->where('status', 'paid')
            ->where(function ($query) use ($fromDate, $toDate, $fromMonth, $toMonth): void {
                $query->where(function ($paidQuery) use ($fromDate, $toDate): void {
                    $paidQuery->whereNotNull('paid_at')
                        ->whereBetween('paid_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59']);
                })->orWhere(function ($fallbackQuery) use ($fromMonth, $toMonth): void {
                    $fallbackQuery->whereNull('paid_at')
                        ->whereDate('month', '>=', $fromMonth)
                        ->whereDate('month', '<=', $toMonth);
                });
            })
            ->get();

        foreach ($salaryPaidEntries as $salaryMonth) {
            $entryDate = $salaryMonth->paid_at
                ? Carbon::parse($salaryMonth->paid_at)->toDateString()
                : optional($salaryMonth->month)->toDateString();

            $this->appendLedgerRow(
                $rows,
                entryDate: $entryDate,
                entryType: 'payroll_payment',
                reference: 'SAL-PAY-'.$salaryMonth->id,
                description: 'Salary paid for '.(optional($salaryMonth->month)->format('Y-m') ?? 'n/a'),
                projectId: null,
                projectName: null,
                clientName: null,
                invoiceId: null,
                invoiceNumber: null,
                debitAccount: $this->accountName('2400'),
                creditAccount: $this->accountName('1100'),
                amount: (float) $salaryMonth->net_payable,
                sortWeight: 50,
                sortId: (int) $salaryMonth->id,
            );
        }

        $assetPurchases = Asset::query()
            ->whereBetween('purchase_date', [$fromDate, $toDate])
            ->get();

        foreach ($assetPurchases as $asset) {
            $this->appendLedgerRow(
                $rows,
                entryDate: optional($asset->purchase_date)->toDateString(),
                entryType: 'asset_purchase',
                reference: 'AST-'.$asset->id,
                description: 'Asset purchased: '.$asset->name,
                projectId: null,
                projectName: null,
                clientName: null,
                invoiceId: null,
                invoiceNumber: null,
                debitAccount: $this->accountName('1500'),
                creditAccount: $this->accountName('1100'),
                amount: (float) $asset->purchase_cost,
                sortWeight: 60,
                sortId: (int) $asset->id,
            );
        }

        $depreciationSeries = $this->depreciationSeries($to->copy()->endOfMonth());
        $fromMonthKey = $from->format('Y-m');
        $toMonthKey = $to->format('Y-m');

        foreach ($depreciationSeries as $monthKey => $amount) {
            if ($monthKey < $fromMonthKey || $monthKey > $toMonthKey || $amount <= 0) {
                continue;
            }

            $entryDate = Carbon::createFromFormat('Y-m-d', $monthKey.'-01')->endOfMonth();
            if ($entryDate->greaterThan($to)) {
                $entryDate = $to->copy();
            }

            $this->appendLedgerRow(
                $rows,
                entryDate: $entryDate->toDateString(),
                entryType: 'depreciation_accrual',
                reference: 'DEP-'.$monthKey,
                description: 'Monthly depreciation accrual for '.$monthKey,
                projectId: null,
                projectName: null,
                clientName: null,
                invoiceId: null,
                invoiceNumber: null,
                debitAccount: $this->accountName('5300'),
                creditAccount: $this->accountName('1590'),
                amount: (float) $amount,
                sortWeight: 70,
                sortId: (int) str_replace('-', '', $monthKey),
            );
        }

        $prepaidAmortizationSeries = $this->prepaidAmortizationSeries($to->copy()->endOfMonth());

        foreach ($prepaidAmortizationSeries as $monthKey => $amount) {
            if ($monthKey < $fromMonthKey || $monthKey > $toMonthKey || $amount <= 0) {
                continue;
            }

            $entryDate = Carbon::createFromFormat('Y-m-d', $monthKey.'-01')->endOfMonth();
            if ($entryDate->greaterThan($to)) {
                $entryDate = $to->copy();
            }

            $this->appendLedgerRow(
                $rows,
                entryDate: $entryDate->toDateString(),
                entryType: 'prepaid_amortization',
                reference: 'PRE-AMZ-'.$monthKey,
                description: 'Prepaid expense amortization for '.$monthKey,
                projectId: null,
                projectName: null,
                clientName: null,
                invoiceId: null,
                invoiceNumber: null,
                debitAccount: $this->accountName('5200'),
                creditAccount: $this->accountName('1250'),
                amount: (float) $amount,
                sortWeight: 75,
                sortId: (int) str_replace('-', '', $monthKey),
            );
        }

        foreach ($this->monthsInRange($from->copy()->startOfMonth(), $to->copy()->startOfMonth()) as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $amount = $this->liabilityMonthlyCostForMonth($monthStart, $monthEnd);

            if ($amount <= 0) {
                continue;
            }

            $entryDate = $monthEnd->lessThanOrEqualTo($to) ? $monthEnd : $to->copy();
            $monthKey = $monthStart->format('Y-m');

            $this->appendLedgerRow(
                $rows,
                entryDate: $entryDate->toDateString(),
                entryType: 'liability_finance_payment',
                reference: 'LIA-COST-'.$monthKey,
                description: 'Liability financing outflow for '.$monthKey,
                projectId: null,
                projectName: null,
                clientName: null,
                invoiceId: null,
                invoiceNumber: null,
                debitAccount: $this->accountName('5400'),
                creditAccount: $this->accountName('1100'),
                amount: (float) $amount,
                sortWeight: 80,
                sortId: (int) str_replace('-', '', $monthKey),
            );
        }

        $equityEntries = OwnerEquityEntry::query()
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        foreach ($equityEntries as $equityEntry) {
            $isContribution = $equityEntry->entry_type === 'capital_contribution';

            $this->appendLedgerRow(
                $rows,
                entryDate: optional($equityEntry->entry_date)->toDateString(),
                entryType: $isContribution ? 'owner_capital' : 'owner_drawing',
                reference: 'EQT-'.$equityEntry->id,
                description: $isContribution ? 'Owner capital contribution' : 'Owner drawing withdrawal',
                projectId: null,
                projectName: null,
                clientName: null,
                invoiceId: null,
                invoiceNumber: null,
                debitAccount: $isContribution ? $this->accountName('1100') : $this->accountName('3300'),
                creditAccount: $isContribution ? $this->accountName('3200') : $this->accountName('1100'),
                amount: (float) $equityEntry->amount,
                sortWeight: 90,
                sortId: (int) $equityEntry->id,
            );
        }

        return $rows;
    }

    private function appendLedgerRow(
        Collection $rows,
        ?string $entryDate,
        string $entryType,
        string $reference,
        string $description,
        ?int $projectId,
        ?string $projectName,
        ?string $clientName,
        ?int $invoiceId,
        ?string $invoiceNumber,
        string $debitAccount,
        string $creditAccount,
        float $amount,
        int $sortWeight,
        int $sortId,
        array $extra = [],
    ): void {
        $roundedAmount = round($amount, 2);
        if ($roundedAmount <= 0) {
            return;
        }

        $rows->push(array_merge([
            'entry_date' => $entryDate,
            'entry_type' => $entryType,
            'reference' => $reference,
            'description' => $description,
            'project_id' => $projectId,
            'project_name' => $projectName,
            'client_name' => $clientName,
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'debit_account' => $debitAccount,
            'credit_account' => $creditAccount,
            'amount' => $roundedAmount,
            'sort_weight' => $sortWeight,
            'sort_id' => $sortId,
        ], $extra));
    }

    private function accountName(string $accountCode): string
    {
        return self::ACCOUNT_NAMES[$accountCode] ?? $accountCode;
    }

    private function accruedRevenueForPeriod(Carbon $from, Carbon $to): float
    {
        return (float) Invoice::query()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
    }

    private function accruedRevenueAsOf(string $asOfDate): float
    {
        return (float) Invoice::query()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->whereDate('invoice_date', '<=', $asOfDate)
            ->sum('amount');
    }

    private function linkedCollectionsAsOf(string $asOfDate): float
    {
        return (float) ProjectPayment::query()
            ->whereNotNull('invoice_id')
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('amount');
    }

    private function unearnedCollectionsAsOf(string $asOfDate): float
    {
        return (float) ProjectPayment::query()
            ->whereNull('invoice_id')
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('amount');
    }

    private function totalCollectionsAsOf(string $asOfDate): float
    {
        return (float) ProjectPayment::query()
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('amount');
    }

    private function accruedOperatingExpenseForPeriod(Carbon $from, Carbon $to): float
    {
        $nonPrepaid = (float) Expense::query()
            ->where('accounting_mode', '!=', 'prepaid')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $prepaidAmortization = $this->prepaidAmortizationForPeriod($from, $to);

        return round($nonPrepaid + $prepaidAmortization, 2);
    }

    private function accruedOperatingExpenseAsOf(string $asOfDate): float
    {
        $asOf = Carbon::parse($asOfDate)->endOfDay();

        $nonPrepaid = (float) Expense::query()
            ->where('accounting_mode', '!=', 'prepaid')
            ->whereDate('expense_date', '<=', $asOf->toDateString())
            ->sum('amount');

        $prepaidAmortization = $this->prepaidAmortizationAsOf($asOf->toDateString());

        return round($nonPrepaid + $prepaidAmortization, 2);
    }

    private function expenseCashPaidForPeriod(Carbon $from, Carbon $to): float
    {
        return round((float) ExpensePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount'), 2);
    }

    private function expenseCashPaidAsOf(string $asOfDate): float
    {
        return round((float) ExpensePayment::query()
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('amount'), 2);
    }

    private function accountsPayableAsOf(string $asOfDate): float
    {
        $expenses = Expense::query()
            ->whereIn('accounting_mode', ['cash', 'payable'])
            ->whereDate('expense_date', '<=', $asOfDate)
            ->withSum([
                'payments as paid_amount' => fn ($query) => $query->whereDate('payment_date', '<=', $asOfDate),
            ], 'amount')
            ->get(['id', 'amount']);

        return round((float) $expenses->sum(static function (Expense $expense): float {
            return max(0, (float) $expense->amount - (float) ($expense->paid_amount ?? 0));
        }), 2);
    }

    private function prepaidUnamortizedAsOf(string $asOfDate): float
    {
        $asOf = Carbon::parse($asOfDate)->endOfDay();

        return round((float) Expense::query()
            ->where('accounting_mode', 'prepaid')
            ->whereDate('expense_date', '<=', $asOf->toDateString())
            ->get(['amount', 'prepaid_start_date', 'prepaid_months'])
            ->sum(function (Expense $expense) use ($asOf): float {
                $amount = round((float) $expense->amount, 2);
                $months = max(1, (int) ($expense->prepaid_months ?? 1));
                $start = $expense->prepaid_start_date
                    ? Carbon::parse($expense->prepaid_start_date)->startOfMonth()
                    : Carbon::parse($expense->expense_date)->startOfMonth();

                $monthsElapsed = max(0, $start->diffInMonths($asOf->copy()->startOfMonth(), false) + 1);
                $recognizedMonths = min($months, $monthsElapsed);
                $recognized = round(($amount / $months) * $recognizedMonths, 2);

                return max(0, round($amount - $recognized, 2));
            }), 2);
    }

    private function prepaidAmortizationForPeriod(Carbon $from, Carbon $to): float
    {
        $fromMonth = $from->copy()->startOfMonth();
        $toMonth = $to->copy()->startOfMonth();

        return round((float) Expense::query()
            ->where('accounting_mode', 'prepaid')
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->get(['amount', 'prepaid_start_date', 'prepaid_months', 'expense_date'])
            ->sum(function (Expense $expense) use ($fromMonth, $toMonth): float {
                $amount = round((float) $expense->amount, 2);
                $months = max(1, (int) ($expense->prepaid_months ?? 1));
                $start = $expense->prepaid_start_date
                    ? Carbon::parse($expense->prepaid_start_date)->startOfMonth()
                    : Carbon::parse($expense->expense_date)->startOfMonth();

                $end = $start->copy()->addMonths($months - 1);

                if ($end->lt($fromMonth) || $start->gt($toMonth)) {
                    return 0;
                }

                $effectiveStart = $start->gt($fromMonth) ? $start : $fromMonth;
                $effectiveEnd = $end->lt($toMonth) ? $end : $toMonth;
                $activeMonths = $effectiveStart->diffInMonths($effectiveEnd) + 1;

                return round(($amount / $months) * $activeMonths, 2);
            }), 2);
    }

    private function prepaidAmortizationAsOf(string $asOfDate): float
    {
        $asOf = Carbon::parse($asOfDate)->endOfDay();

        return round((float) Expense::query()
            ->where('accounting_mode', 'prepaid')
            ->whereDate('expense_date', '<=', $asOf->toDateString())
            ->get(['amount', 'prepaid_start_date', 'prepaid_months', 'expense_date'])
            ->sum(function (Expense $expense) use ($asOf): float {
                $amount = round((float) $expense->amount, 2);
                $months = max(1, (int) ($expense->prepaid_months ?? 1));
                $start = $expense->prepaid_start_date
                    ? Carbon::parse($expense->prepaid_start_date)->startOfMonth()
                    : Carbon::parse($expense->expense_date)->startOfMonth();

                $monthsElapsed = max(0, $start->diffInMonths($asOf->copy()->startOfMonth(), false) + 1);
                $recognizedMonths = min($months, $monthsElapsed);

                return round(($amount / $months) * $recognizedMonths, 2);
            }), 2);
    }

    private function assetPurchasesForPeriod(Carbon $from, Carbon $to): float
    {
        return (float) Asset::query()
            ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->sum('purchase_cost');
    }

    private function assetPurchasesAsOf(string $asOfDate): float
    {
        return (float) Asset::query()
            ->whereDate('purchase_date', '<=', $asOfDate)
            ->sum('purchase_cost');
    }

    private function accumulatedDepreciationAsOf(string $asOfDate): float
    {
        return (float) Asset::query()
            ->whereDate('purchase_date', '<=', $asOfDate)
            ->get(['purchase_cost', 'current_book_value'])
            ->sum(static fn (Asset $asset): float => max(0, (float) $asset->purchase_cost - (float) $asset->current_book_value));
    }

    /**
     * @return array{gross: float, net: float, tax: float, recoveries: float, tds: float}
     */
    private function payrollAccruedComponentsForMonth(Carbon $monthStart): array
    {
        $row = SalaryMonth::query()
            ->whereIn('status', self::PAYROLL_ACCRUAL_STATUSES)
            ->whereDate('month', $monthStart->toDateString())
            ->selectRaw('COALESCE(SUM(gross_earnings), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(net_payable), 0) as net_total')
            ->selectRaw('COALESCE(SUM(tds_deduction + pf_deduction + professional_tax), 0) as tax_total')
            ->selectRaw('COALESCE(SUM(unpaid_leave_deduction + late_penalty_deduction + loan_emi_deduction), 0) as recoveries_total')
            ->selectRaw('COALESCE(SUM(tds_deduction), 0) as tds_total')
            ->first();

        return [
            'gross' => round((float) ($row->gross_total ?? 0), 2),
            'net' => round((float) ($row->net_total ?? 0), 2),
            'tax' => round((float) ($row->tax_total ?? 0), 2),
            'recoveries' => round((float) ($row->recoveries_total ?? 0), 2),
            'tds' => round((float) ($row->tds_total ?? 0), 2),
        ];
    }

    /**
     * @return array{gross: float, net: float, tax: float, recoveries: float, tds: float}
     */
    private function payrollAccruedComponentsAsOf(Carbon $asOfMonthStart): array
    {
        $row = SalaryMonth::query()
            ->whereIn('status', self::PAYROLL_ACCRUAL_STATUSES)
            ->whereDate('month', '<=', $asOfMonthStart->toDateString())
            ->selectRaw('COALESCE(SUM(gross_earnings), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(net_payable), 0) as net_total')
            ->selectRaw('COALESCE(SUM(tds_deduction + pf_deduction + professional_tax), 0) as tax_total')
            ->selectRaw('COALESCE(SUM(unpaid_leave_deduction + late_penalty_deduction + loan_emi_deduction), 0) as recoveries_total')
            ->selectRaw('COALESCE(SUM(tds_deduction), 0) as tds_total')
            ->first();

        return [
            'gross' => round((float) ($row->gross_total ?? 0), 2),
            'net' => round((float) ($row->net_total ?? 0), 2),
            'tax' => round((float) ($row->tax_total ?? 0), 2),
            'recoveries' => round((float) ($row->recoveries_total ?? 0), 2),
            'tds' => round((float) ($row->tds_total ?? 0), 2),
        ];
    }

    private function payrollPaidForPeriod(Carbon $from, Carbon $to): float
    {
        return (float) SalaryMonth::query()
            ->where('status', 'paid')
            ->where(function ($query) use ($from, $to): void {
                $query->where(function ($paidAtQuery) use ($from, $to): void {
                    $paidAtQuery->whereNotNull('paid_at')
                        ->whereBetween('paid_at', [$from->toDateTimeString(), $to->toDateTimeString()]);
                })->orWhere(function ($fallbackQuery) use ($from, $to): void {
                    $fallbackQuery->whereNull('paid_at')
                        ->whereDate('month', '>=', $from->copy()->startOfMonth()->toDateString())
                        ->whereDate('month', '<=', $to->copy()->startOfMonth()->toDateString());
                });
            })
            ->sum('net_payable');
    }

    private function payrollPaidAsOf(Carbon $asOf): float
    {
        return (float) SalaryMonth::query()
            ->where('status', 'paid')
            ->where(function ($query) use ($asOf): void {
                $query->where(function ($paidAtQuery) use ($asOf): void {
                    $paidAtQuery->whereNotNull('paid_at')
                        ->whereDate('paid_at', '<=', $asOf->toDateString());
                })->orWhere(function ($fallbackQuery) use ($asOf): void {
                    $fallbackQuery->whereNull('paid_at')
                        ->whereDate('month', '<=', $asOf->copy()->startOfMonth()->toDateString());
                });
            })
            ->sum('net_payable');
    }

    private function salaryPayableAsOf(Carbon $asOfMonthStart, float $accruedNet): float
    {
        $paidNet = (float) SalaryMonth::query()
            ->where('status', 'paid')
            ->where(function ($query) use ($asOfMonthStart): void {
                $query->where(function ($paidAtQuery) use ($asOfMonthStart): void {
                    $paidAtQuery->whereNotNull('paid_at')
                        ->whereDate('paid_at', '<=', $asOfMonthStart->copy()->endOfMonth()->toDateString());
                })->orWhere(function ($fallbackQuery) use ($asOfMonthStart): void {
                    $fallbackQuery->whereNull('paid_at')
                        ->whereDate('month', '<=', $asOfMonthStart->toDateString());
                });
            })
            ->sum('net_payable');

        return round(max(0, $accruedNet - $paidNet), 2);
    }

    private function cashPositionAsOf(Carbon $asOf): float
    {
        $asOfDate = $asOf->toDateString();

        $cashIn = $this->totalCollectionsAsOf($asOfDate);
        $operatingOut = $this->expenseCashPaidAsOf($asOfDate);
        $payrollOut = $this->payrollPaidAsOf($asOf);
        $assetOut = $this->assetPurchasesAsOf($asOfDate);
        $liabilityOut = $this->liabilityCostAsOf($asOf);

        return round($cashIn - $operatingOut - $payrollOut - $assetOut - $liabilityOut, 2);
    }

    /**
     * @return array<string, float>
     */
    private function depreciationSeries(Carbon $asOf): array
    {
        $series = [];

        $assets = Asset::query()
            ->whereDate('purchase_date', '<=', $asOf->toDateString())
            ->get(['purchase_date', 'purchase_cost', 'current_book_value', 'monthly_depreciation']);

        foreach ($assets as $asset) {
            $monthly = round((float) $asset->monthly_depreciation, 2);
            if ($monthly <= 0) {
                continue;
            }

            $accumulated = round(max(0, (float) $asset->purchase_cost - (float) $asset->current_book_value), 2);
            if ($accumulated <= 0) {
                continue;
            }

            $cursor = Carbon::parse($asset->purchase_date)->startOfMonth();
            $remaining = $accumulated;

            while ($remaining > 0.009 && $cursor->lessThanOrEqualTo($asOf->copy()->startOfMonth())) {
                $allocation = round(min($monthly, $remaining), 2);
                $key = $cursor->format('Y-m');
                $series[$key] = round(($series[$key] ?? 0) + $allocation, 2);

                $remaining = round($remaining - $allocation, 2);
                $cursor->addMonth();
            }
        }

        ksort($series);

        return $series;
    }

    /**
     * @return array<string, float>
     */
    private function prepaidAmortizationSeries(Carbon $asOf): array
    {
        $series = [];

        $prepaidExpenses = Expense::query()
            ->where('accounting_mode', 'prepaid')
            ->whereDate('expense_date', '<=', $asOf->toDateString())
            ->get(['expense_date', 'amount', 'prepaid_start_date', 'prepaid_months']);

        foreach ($prepaidExpenses as $expense) {
            $totalAmount = round((float) $expense->amount, 2);
            if ($totalAmount <= 0) {
                continue;
            }

            $months = max(1, (int) ($expense->prepaid_months ?? 1));
            $monthly = round($totalAmount / $months, 2);
            if ($monthly <= 0) {
                continue;
            }

            $cursor = $expense->prepaid_start_date
                ? Carbon::parse($expense->prepaid_start_date)->startOfMonth()
                : Carbon::parse($expense->expense_date)->startOfMonth();

            $remaining = $totalAmount;
            for ($i = 0; $i < $months; $i++) {
                if ($remaining <= 0.009 || $cursor->greaterThan($asOf->copy()->startOfMonth())) {
                    break;
                }

                $allocation = round(min($monthly, $remaining), 2);
                $key = $cursor->format('Y-m');
                $series[$key] = round(($series[$key] ?? 0) + $allocation, 2);

                $remaining = round($remaining - $allocation, 2);
                $cursor->addMonth();
            }
        }

        ksort($series);

        return $series;
    }

    private function ownerCapitalAsOf(string $asOfDate): float
    {
        return round((float) OwnerEquityEntry::query()
            ->where('entry_type', 'capital_contribution')
            ->whereDate('entry_date', '<=', $asOfDate)
            ->sum('amount'), 2);
    }

    private function ownerDrawingsAsOf(string $asOfDate): float
    {
        return round((float) OwnerEquityEntry::query()
            ->where('entry_type', 'drawing')
            ->whereDate('entry_date', '<=', $asOfDate)
            ->sum('amount'), 2);
    }

    private function liabilityCostAsOf(Carbon $asOf): float
    {
        $liabilities = Liability::query()
            ->whereDate('start_date', '<=', $asOf->toDateString())
            ->get(['start_date', 'end_date', 'monthly_payment']);

        $total = 0.0;
        $asOfMonth = $asOf->copy()->startOfMonth();

        foreach ($liabilities as $liability) {
            $monthlyPayment = (float) $liability->monthly_payment;
            if ($monthlyPayment <= 0) {
                continue;
            }

            $start = Carbon::parse($liability->start_date)->startOfMonth();
            $end = $liability->end_date
                ? Carbon::parse($liability->end_date)->startOfMonth()
                : $asOfMonth->copy();

            if ($end->greaterThan($asOfMonth)) {
                $end = $asOfMonth->copy();
            }

            if ($start->greaterThan($end)) {
                continue;
            }

            $months = $start->diffInMonths($end) + 1;
            $total += $months * $monthlyPayment;
        }

        return round($total, 2);
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

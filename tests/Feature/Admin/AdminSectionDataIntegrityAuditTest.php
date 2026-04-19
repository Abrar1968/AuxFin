<?php

use App\Algorithms\CMGR;
use App\Models\Asset;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\CompanySnapshot;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeMessage;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Liability;
use App\Models\Loan;
use App\Models\OwnerEquityEntry;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Models\PublicHoliday;
use App\Models\SalaryMonth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('every admin section endpoint returns proper seeded data payloads', function () {
    $seed = seedAdminSectionAuditData();
    Sanctum::actingAs($seed['admin']);
    Cache::flush();

    $this->getJson('/api/admin/finance/overview')
        ->assertOk()
        ->assertJsonPath('kpis.projects', 1)
        ->assertJsonPath('kpis.clients', 1)
        ->assertJsonPath('project_rows.0.id', $seed['project']->id);

    $this->getJson('/api/admin/departments')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['department']->id);

    $this->getJson('/api/admin/employees')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['employee']->id);

    $this->getJson("/api/admin/employees/{$seed['employee']->id}")
        ->assertOk()
        ->assertJsonPath('id', $seed['employee']->id);

    $this->getJson('/api/admin/clients')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['client']->id);

    $this->getJson('/api/admin/projects')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['project']->id);

    $invoiceRows = $this->getJson("/api/admin/projects/{$seed['project']->id}/invoices")
        ->assertOk()
        ->json('data');

    expect(collect($invoiceRows)->pluck('id')->all())
        ->toContain($seed['invoiceLinked']->id)
        ->toContain($seed['invoicePartial']->id);

    $paymentRows = $this->getJson("/api/admin/projects/{$seed['project']->id}/payments")
        ->assertOk()
        ->json('data');

    expect(collect($paymentRows)->pluck('id')->all())
        ->toContain($seed['linkedPayment']->id)
        ->toContain($seed['advancePayment']->id);

    $this->getJson('/api/admin/expenses')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['expensePayable']->id);

    $this->getJson('/api/admin/expenses-summary')
        ->assertOk()
        ->assertJsonStructure([
            'monthly_total',
            'recurring_total',
            'outstanding_payables',
            'category_totals',
        ]);

    $this->getJson("/api/admin/expenses/{$seed['expensePayable']->id}/payments")
        ->assertOk()
        ->assertJsonPath('rows.0.id', $seed['expensePayablePayment']->id);

    $this->getJson('/api/admin/owner-equity')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['ownerEquity']->id);

    $this->getJson('/api/admin/liabilities')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['liability']->id);

    $this->getJson('/api/admin/assets')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['asset']->id);

    $this->getJson('/api/admin/loans')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['loan']->id);

    $this->getJson('/api/admin/leaves')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['leave']->id);

    $this->getJson('/api/admin/attendance?employee_id='.$seed['employee']->id.'&month=2026-04-01')
        ->assertOk()
        ->assertJsonPath('records.0.id', $seed['attendance']->id);

    $this->getJson('/api/admin/messages')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['message']->id);

    $this->getJson('/api/admin/settings/general')->assertOk();
    $this->getJson('/api/admin/settings/late-policy')->assertOk();
    $this->getJson('/api/admin/settings/loan-policy')->assertOk();
    $this->getJson('/api/admin/settings/tax-policy')->assertOk();
    $this->getJson('/api/admin/settings/holidays')
        ->assertOk()
        ->assertJsonPath('data.0.id', $seed['holiday']->id);

    $this->getJson('/api/admin/payroll/2026-04-01')
        ->assertOk()
        ->assertJsonPath('0.id', $seed['salaryMonth']->id);

    $this->getJson("/api/admin/payroll/{$seed['salaryMonth']->id}/payslip")
        ->assertOk()
        ->assertJsonPath('employee.employee_code', $seed['employee']->employee_code);

    $this->getJson('/api/admin/analytics/overview')
        ->assertOk()
        ->assertJsonPath('latest.id', $seed['snapshots']['mar']->id);

    $this->getJson('/api/admin/analytics/cmgr')->assertOk();
    $this->getJson('/api/admin/analytics/forecast')->assertOk();
    $this->getJson('/api/admin/analytics/anomalies')->assertOk();
    $this->getJson('/api/admin/analytics/ar-health')->assertOk();
    $this->getJson('/api/admin/analytics/growth')->assertOk();
    $this->getJson('/api/admin/analytics/burn-rate?available_cash=100000')->assertOk();

    $this->getJson('/api/admin/reports/profit-loss?from_month=2026-04-01&to_month=2026-04-30')->assertOk();
    $this->getJson('/api/admin/reports/tax-summary?from_month=2026-04-01&to_month=2026-04-30')->assertOk();
    $this->getJson('/api/admin/reports/ar-aging?as_of=2026-04-30')->assertOk();
    $this->getJson('/api/admin/reports/trial-balance?as_of=2026-04-30')->assertOk();
    $this->getJson('/api/admin/reports/balance-sheet?as_of=2026-04-30')->assertOk();
    $this->getJson('/api/admin/reports/cash-flow?from_month=2026-04-01&to_month=2026-04-30')->assertOk();
    $this->getJson('/api/admin/reports/general-ledger?from_date=2026-04-01&to_date=2026-04-30&per_page=200')->assertOk();
    $this->getJson('/api/admin/reports/payment-ledger?from_date=2026-04-01&to_date=2026-04-30&per_page=200')->assertOk();
});

test('client options mode returns lightweight rows for dropdown loading', function () {
    $seed = seedAdminSectionAuditData();
    Sanctum::actingAs($seed['admin']);

    $payload = $this->getJson('/api/admin/clients?options=1&limit=500')
        ->assertOk()
        ->json();

    $target = collect($payload)->firstWhere('id', $seed['client']->id);

    expect($target)->not->toBeNull();
    expect(array_key_exists('name', $target))->toBeTrue();
    expect(array_key_exists('email', $target))->toBeFalse();
});

test('finance overview and project accrual kpis stay consistent with linked collections', function () {
    $seed = seedAdminSectionAuditData();
    Sanctum::actingAs($seed['admin']);
    Cache::flush();

    $overview = $this->getJson('/api/admin/finance/overview')->assertOk()->json();
    $kpis = $overview['kpis'];

    expect((float) $kpis['booked_revenue'])->toBe(1500.0);
    expect((float) $kpis['recognized_revenue'])->toBe(1500.0);
    expect((float) $kpis['cash_collected'])->toBe(300.0);
    expect((float) $kpis['advance_collections'])->toBe(200.0);
    expect((float) $kpis['accounts_receivable'])->toBe(1200.0);
    expect((float) $kpis['collection_rate_percent'])->toBe(20.0);

    $projectRow = collect($overview['project_rows'] ?? [])->firstWhere('id', $seed['project']->id);
    expect($projectRow)->not->toBeNull();

    expect((float) ($projectRow['recognized_revenue'] ?? 0))->toBe(1500.0);
    expect((float) ($projectRow['cash_collected'] ?? 0))->toBe(300.0);
    expect((float) ($projectRow['advance_collections'] ?? 0))->toBe(200.0);
    expect((float) ($projectRow['accounts_receivable'] ?? 0))->toBe(1200.0);

    $projectRevenue = $this->getJson("/api/admin/projects/{$seed['project']->id}/revenue")
        ->assertOk()
        ->json('summary');

    expect((float) ($projectRevenue['cash_collected'] ?? 0))->toBe(300.0);
    expect((float) ($projectRevenue['advance_collections'] ?? 0))->toBe(200.0);
    expect((float) ($projectRevenue['accounts_receivable'] ?? 0))->toBe(1200.0);

    $projectListRow = collect(
        $this->getJson('/api/admin/projects')->assertOk()->json('data') ?? []
    )->firstWhere('id', $seed['project']->id);

    expect((float) ($projectListRow['cash_collected'] ?? 0))->toBe(300.0);
    expect((float) ($projectListRow['advance_collections'] ?? 0))->toBe(200.0);
    expect((float) ($projectListRow['accounts_receivable'] ?? 0))->toBe(1200.0);
});

test('analytics and reporting equations remain internally consistent', function () {
    $seed = seedAdminSectionAuditData();
    Sanctum::actingAs($seed['admin']);
    Cache::flush();

    $trial = $this->getJson('/api/admin/reports/trial-balance?as_of=2026-04-30')->assertOk()->json();
    expect((bool) ($trial['is_balanced'] ?? false))->toBeTrue();
    expect(abs((float) data_get($trial, 'totals.debit', 0) - (float) data_get($trial, 'totals.credit', 0)))->toBeLessThanOrEqual(0.01);

    $balance = $this->getJson('/api/admin/reports/balance-sheet?as_of=2026-04-30')->assertOk()->json();
    expect((bool) ($balance['is_balanced'] ?? false))->toBeTrue();
    expect(abs((float) data_get($balance, 'totals.assets', 0) - (float) data_get($balance, 'totals.liabilities_plus_equity', 0)))->toBeLessThanOrEqual(0.01);

    $aging = $this->getJson('/api/admin/reports/ar-aging?as_of=2026-04-30')->assertOk()->json();
    $bucketTotal = collect(data_get($aging, 'distribution', []))
        ->sum(fn ($bucket) => (float) ($bucket['amount'] ?? 0));
    expect(abs(round($bucketTotal, 2) - (float) data_get($aging, 'total_outstanding', 0)))->toBeLessThanOrEqual(0.01);

    $cashFlow = $this->getJson('/api/admin/reports/cash-flow?from_month=2026-04-01&to_month=2026-04-30')->assertOk()->json();
    $rows = collect($cashFlow['rows'] ?? []);
    expect($rows->count())->toBeGreaterThan(0);

    foreach ($rows as $row) {
        $opening = (float) ($row['opening_balance'] ?? 0);
        $cashIn = (float) ($row['cash_in_collections'] ?? 0);
        $operating = (float) ($row['operating_outflow'] ?? 0);
        $financing = (float) ($row['financing_outflow'] ?? 0);
        $net = (float) ($row['net_cash_flow'] ?? 0);
        $closing = (float) ($row['closing_balance'] ?? 0);

        expect(abs(round($cashIn - $operating - $financing, 2) - $net))->toBeLessThanOrEqual(0.01);
        expect(abs(round($opening + $net, 2) - $closing))->toBeLessThanOrEqual(0.01);
    }

    expect(abs((float) data_get($cashFlow, 'totals.cash_in_collections', 0) - round((float) $rows->sum('cash_in_collections'), 2)))->toBeLessThanOrEqual(0.01);
    expect(abs((float) data_get($cashFlow, 'totals.operating_outflow', 0) - round((float) $rows->sum('operating_outflow'), 2)))->toBeLessThanOrEqual(0.01);
    expect(abs((float) data_get($cashFlow, 'totals.financing_outflow', 0) - round((float) $rows->sum('financing_outflow'), 2)))->toBeLessThanOrEqual(0.01);

    $paymentLedger = $this->getJson('/api/admin/reports/payment-ledger?from_date=2026-04-01&to_date=2026-04-30&per_page=200')->assertOk()->json();
    $ledgerAmount = round((float) collect($paymentLedger['entries'] ?? [])->sum('amount'), 2);
    expect(abs($ledgerAmount - (float) data_get($paymentLedger, 'summary.total_amount', 0)))->toBeLessThanOrEqual(0.01);

    $cmgr = $this->getJson('/api/admin/analytics/cmgr')->assertOk()->json();

    $expectedRevenueCmgr = round(CMGR::calculate(1000, 1210, 2), 2);
    $expectedPayrollCmgr = round(CMGR::calculate(400, 441, 2), 2);
    $expectedNetProfitCmgr = round(CMGR::calculate(300, 429, 2), 2);
    $expectedArCmgr = round(CMGR::calculate(500, 405, 2), 2);

    expect((float) ($cmgr['revenue_cmgr'] ?? 0))->toBe($expectedRevenueCmgr);
    expect((float) ($cmgr['payroll_cmgr'] ?? 0))->toBe($expectedPayrollCmgr);
    expect((float) ($cmgr['net_profit_cmgr'] ?? 0))->toBe($expectedNetProfitCmgr);
    expect((float) ($cmgr['ar_cmgr'] ?? 0))->toBe($expectedArCmgr);

    $growth = $this->getJson('/api/admin/analytics/growth')->assertOk()->json();
    expect((float) data_get($growth, 'revenue_quality_score', 0))->toBe(round(((1210 - 405) / 1210) * 100, 2));
    expect((string) data_get($growth, 'payroll_efficiency.status', ''))->toBe('target');
});

test('day week month and year timeframe payloads stay internally consistent', function () {
    $seed = seedAdminSectionAuditData();
    Sanctum::actingAs($seed['admin']);
    Cache::flush();

    $anchor = '2026-04-30';
    $timeframes = ['day', 'week', 'month', 'year'];

    foreach ($timeframes as $timeframe) {
        $range = timeframeRangeForAudit($anchor, $timeframe);
        $from = $range['from'];
        $to = $range['to'];

        $finance = $this->getJson("/api/admin/finance/overview?timeframe={$timeframe}&anchor_date={$anchor}")
            ->assertOk()
            ->json();

        expect((string) data_get($finance, 'timeframe', ''))->toBe($timeframe);
        expect((string) data_get($finance, 'period_to', ''))->toBe($to);

        $profitLoss = $this->getJson("/api/admin/reports/profit-loss?timeframe={$timeframe}&anchor_date={$anchor}&from_month={$from}&to_month={$to}")
            ->assertOk()
            ->json();

        foreach (collect($profitLoss['rows'] ?? []) as $row) {
            $revenue = (float) ($row['revenue'] ?? 0);
            $payroll = (float) ($row['payroll'] ?? 0);
            $opex = (float) ($row['opex'] ?? 0);
            $liabilityCost = (float) ($row['liability_cost'] ?? 0);
            $grossProfit = (float) ($row['gross_profit'] ?? 0);
            $netProfit = (float) ($row['net_profit'] ?? 0);
            $estimatedTax = (float) ($row['estimated_tax'] ?? 0);
            $profitAfterTax = (float) ($row['profit_after_tax'] ?? 0);

            expect(abs(round($revenue - $payroll, 2) - $grossProfit))->toBeLessThanOrEqual(0.01);
            expect(abs(round($grossProfit - $opex - $liabilityCost, 2) - $netProfit))->toBeLessThanOrEqual(0.01);
            expect(abs(round($netProfit - $estimatedTax, 2) - $profitAfterTax))->toBeLessThanOrEqual(0.01);
        }

        $cashFlow = $this->getJson("/api/admin/reports/cash-flow?timeframe={$timeframe}&anchor_date={$anchor}&from_month={$from}&to_month={$to}")
            ->assertOk()
            ->json();

        foreach (collect($cashFlow['rows'] ?? []) as $row) {
            $opening = (float) ($row['opening_balance'] ?? 0);
            $cashIn = (float) ($row['cash_in_collections'] ?? 0);
            $operating = (float) ($row['operating_outflow'] ?? 0);
            $financing = (float) ($row['financing_outflow'] ?? 0);
            $net = (float) ($row['net_cash_flow'] ?? 0);
            $closing = (float) ($row['closing_balance'] ?? 0);

            expect(abs(round($cashIn - $operating - $financing, 2) - $net))->toBeLessThanOrEqual(0.01);
            expect(abs(round($opening + $net, 2) - $closing))->toBeLessThanOrEqual(0.01);
        }

        $trial = $this->getJson("/api/admin/reports/trial-balance?timeframe={$timeframe}&anchor_date={$anchor}&as_of={$to}")
            ->assertOk()
            ->json();

        expect((bool) data_get($trial, 'is_balanced', false))->toBeTrue();

        $balance = $this->getJson("/api/admin/reports/balance-sheet?timeframe={$timeframe}&anchor_date={$anchor}&as_of={$to}")
            ->assertOk()
            ->json();

        expect((bool) data_get($balance, 'is_balanced', false))->toBeTrue();

        $aging = $this->getJson("/api/admin/reports/ar-aging?timeframe={$timeframe}&anchor_date={$anchor}&as_of={$to}")
            ->assertOk()
            ->json();

        $agingBucketTotal = collect(data_get($aging, 'distribution', []))
            ->sum(fn ($bucket) => (float) ($bucket['amount'] ?? 0));

        expect(abs(round($agingBucketTotal, 2) - (float) data_get($aging, 'total_outstanding', 0)))->toBeLessThanOrEqual(0.01);

        $paymentLedger = $this->getJson("/api/admin/reports/payment-ledger?timeframe={$timeframe}&anchor_date={$anchor}&from_date={$from}&to_date={$to}&per_page=200")
            ->assertOk()
            ->json();

        $ledgerAmount = round((float) collect($paymentLedger['entries'] ?? [])->sum('amount'), 2);
        expect(abs($ledgerAmount - (float) data_get($paymentLedger, 'summary.total_amount', 0)))->toBeLessThanOrEqual(0.01);

        $overview = $this->getJson("/api/admin/analytics/overview?timeframe={$timeframe}&anchor_date={$anchor}")
            ->assertOk()
            ->json();
        expect((string) data_get($overview, 'timeframe', ''))->toBe($timeframe);

        $cmgr = $this->getJson("/api/admin/analytics/cmgr?timeframe={$timeframe}&anchor_date={$anchor}")
            ->assertOk()
            ->json();
        expect((string) data_get($cmgr, 'timeframe', ''))->toBe($timeframe);

        $growth = $this->getJson("/api/admin/analytics/growth?timeframe={$timeframe}&anchor_date={$anchor}")
            ->assertOk()
            ->json();
        expect((string) data_get($growth, 'timeframe', ''))->toBe($timeframe);
    }
});

/**
 * @return array{from: string, to: string}
 */
function timeframeRangeForAudit(string $anchorDate, string $timeframe): array
{
    $anchor = Carbon::parse($anchorDate)->startOfDay();

    return match ($timeframe) {
        'day' => [
            'from' => $anchor->copy()->startOfDay()->toDateString(),
            'to' => $anchor->copy()->endOfDay()->toDateString(),
        ],
        'week' => [
            'from' => $anchor->copy()->startOfWeek()->toDateString(),
            'to' => $anchor->copy()->endOfWeek()->toDateString(),
        ],
        'year' => [
            'from' => $anchor->copy()->startOfYear()->toDateString(),
            'to' => $anchor->copy()->endOfYear()->toDateString(),
        ],
        default => [
            'from' => $anchor->copy()->startOfMonth()->toDateString(),
            'to' => $anchor->copy()->endOfMonth()->toDateString(),
        ],
    };
}

/**
 * @return array<string, mixed>
 */
function seedAdminSectionAuditData(): array
{
    $admin = User::factory()->create([
        'role' => 'admin',
        'name' => 'Audit Admin',
        'email' => 'audit-admin@auxfin.test',
    ]);

    $employeeUser = User::factory()->create([
        'role' => 'employee',
        'name' => 'Audit Employee',
        'email' => 'audit-employee@auxfin.test',
    ]);

    $department = Department::query()->create([
        'name' => 'Operations',
    ]);

    $employee = Employee::query()->create([
        'user_id' => $employeeUser->id,
        'employee_code' => 'EMP-AUD-001',
        'department_id' => $department->id,
        'designation' => 'Operations Analyst',
        'date_of_joining' => '2025-01-10',
        'basic_salary' => 30000,
        'house_rent' => 10000,
        'conveyance' => 3000,
        'medical_allowance' => 2000,
        'pf_rate' => 8,
        'tds_rate' => 5,
        'professional_tax' => 300,
        'working_days_per_week' => 5,
        'weekly_off_days' => ['friday', 'saturday'],
    ]);

    $department->update(['head_id' => $employee->id]);

    $salaryMonth = SalaryMonth::query()->create([
        'employee_id' => $employee->id,
        'month' => '2026-04-01',
        'basic_salary' => 30000,
        'house_rent' => 10000,
        'conveyance' => 3000,
        'medical_allowance' => 2000,
        'gross_earnings' => 45000,
        'tds_deduction' => 1800,
        'pf_deduction' => 1200,
        'professional_tax' => 300,
        'unpaid_leave_deduction' => 0,
        'late_penalty_deduction' => 0,
        'loan_emi_deduction' => 0,
        'total_deductions' => 3300,
        'net_payable' => 41700,
        'days_present' => 20,
        'unpaid_leave_days' => 0,
        'late_entries' => 1,
        'expected_working_days' => 22,
        'status' => 'processed',
        'processed_at' => now(),
        'processed_by' => $admin->id,
    ]);

    $loan = Loan::query()->create([
        'employee_id' => $employee->id,
        'loan_reference' => 'LOAN-AUD-001',
        'amount_requested' => 5000,
        'reason' => 'Equipment support',
        'status' => 'pending',
    ]);

    $leave = Leave::query()->create([
        'employee_id' => $employee->id,
        'leave_type' => 'casual',
        'from_date' => '2026-04-12',
        'to_date' => '2026-04-13',
        'days' => 2,
        'reason' => 'Family commitment',
        'status' => 'pending',
    ]);

    $attendance = Attendance::query()->create([
        'employee_id' => $employee->id,
        'date' => '2026-04-15',
        'status' => 'present',
        'check_in' => '09:00',
        'check_out' => '18:00',
        'is_late' => false,
        'late_minutes' => 0,
    ]);

    $message = EmployeeMessage::query()->create([
        'employee_id' => $employee->id,
        'type' => 'general_hr',
        'subject' => 'Audit test message',
        'body' => 'Need clarification for monthly process.',
        'status' => 'open',
        'priority' => 'normal',
    ]);

    $holiday = PublicHoliday::query()->create([
        'name' => 'Audit Holiday',
        'date' => '2026-04-20',
        'is_optional' => false,
    ]);

    $client = Client::query()->create([
        'name' => 'Audit Client',
        'email' => 'client@audit.test',
    ]);

    $project = Project::query()->create([
        'client_id' => $client->id,
        'name' => 'Audit Project',
        'contract_amount' => 5000,
        'status' => 'active',
        'start_date' => '2026-04-01',
    ]);

    $invoiceLinked = Invoice::query()->create([
        'project_id' => $project->id,
        'invoice_number' => 'INV-AUD-001',
        'amount' => 1000,
        'invoice_date' => '2026-04-01',
        'due_date' => '2026-04-15',
        'status' => 'sent',
    ]);

    $invoicePartial = Invoice::query()->create([
        'project_id' => $project->id,
        'invoice_number' => 'INV-AUD-002',
        'amount' => 500,
        'invoice_date' => '2026-04-05',
        'due_date' => '2026-04-18',
        'status' => 'partial',
        'partial_amount' => 300,
    ]);

    $linkedPayment = ProjectPayment::query()->create([
        'project_id' => $project->id,
        'invoice_id' => $invoiceLinked->id,
        'recorded_by' => $admin->id,
        'payment_date' => '2026-04-10',
        'amount' => 300,
        'payment_method' => 'bank_transfer',
        'reference_number' => 'AUD-LINK-001',
    ]);

    $advancePayment = ProjectPayment::query()->create([
        'project_id' => $project->id,
        'invoice_id' => null,
        'recorded_by' => $admin->id,
        'payment_date' => '2026-04-11',
        'amount' => 200,
        'payment_method' => 'bank_transfer',
        'reference_number' => 'AUD-ADV-001',
    ]);

    $expensePayable = Expense::query()->create([
        'category' => 'Operations',
        'description' => 'Vendor support invoice',
        'amount' => 400,
        'accounting_mode' => 'payable',
        'expense_date' => '2026-04-08',
        'payable_due_date' => '2026-04-30',
        'created_by' => $admin->id,
    ]);

    $expensePayablePayment = ExpensePayment::query()->create([
        'expense_id' => $expensePayable->id,
        'recorded_by' => $admin->id,
        'payment_date' => '2026-04-20',
        'amount' => 150,
        'payment_method' => 'bank_transfer',
        'reference_number' => 'AUD-EXP-001',
    ]);

    $expensePrepaid = Expense::query()->create([
        'category' => 'Insurance',
        'description' => 'Prepaid policy',
        'amount' => 600,
        'accounting_mode' => 'prepaid',
        'expense_date' => '2026-04-01',
        'prepaid_start_date' => '2026-04-01',
        'prepaid_months' => 6,
        'created_by' => $admin->id,
    ]);

    ExpensePayment::query()->create([
        'expense_id' => $expensePrepaid->id,
        'recorded_by' => $admin->id,
        'payment_date' => '2026-04-01',
        'amount' => 600,
        'payment_method' => 'prepaid',
        'reference_number' => 'AUD-PRE-001',
    ]);

    $expenseCash = Expense::query()->create([
        'category' => 'Utilities',
        'description' => 'Office utility payment',
        'amount' => 250,
        'accounting_mode' => 'cash',
        'expense_date' => '2026-04-02',
        'created_by' => $admin->id,
    ]);

    ExpensePayment::query()->create([
        'expense_id' => $expenseCash->id,
        'recorded_by' => $admin->id,
        'payment_date' => '2026-04-02',
        'amount' => 250,
        'payment_method' => 'cash',
        'reference_number' => 'AUD-CASH-001',
    ]);

    $liability = Liability::query()->create([
        'name' => 'Audit Term Loan',
        'principal_amount' => 10000,
        'outstanding' => 5000,
        'interest_rate' => 8,
        'monthly_payment' => 500,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'next_due_date' => '2026-04-25',
        'status' => 'active',
    ]);

    $asset = Asset::query()->create([
        'name' => 'Audit Laptop Pool',
        'category' => 'IT Equipment',
        'purchase_date' => '2026-01-01',
        'purchase_cost' => 1200,
        'current_book_value' => 1000,
        'useful_life_months' => 12,
        'monthly_depreciation' => 100,
        'status' => 'active',
    ]);

    $ownerEquity = OwnerEquityEntry::query()->create([
        'entry_date' => '2026-04-05',
        'entry_type' => 'capital_contribution',
        'amount' => 3000,
        'notes' => 'Owner injected working capital',
        'recorded_by' => $admin->id,
    ]);

    $snapshotJan = CompanySnapshot::query()->create([
        'snapshot_month' => '2026-01-01',
        'total_revenue' => 1000,
        'total_cash_collected' => 700,
        'total_payroll' => 400,
        'total_opex' => 300,
        'gross_profit' => 600,
        'net_profit' => 300,
        'burn_rate' => 200,
        'cash_runway_months' => 10,
        'headcount' => 10,
        'total_ar' => 500,
    ]);

    $snapshotFeb = CompanySnapshot::query()->create([
        'snapshot_month' => '2026-02-01',
        'total_revenue' => 1100,
        'total_cash_collected' => 780,
        'total_payroll' => 420,
        'total_opex' => 320,
        'gross_profit' => 680,
        'net_profit' => 360,
        'burn_rate' => 180,
        'cash_runway_months' => 11,
        'headcount' => 11,
        'total_ar' => 450,
    ]);

    $snapshotMar = CompanySnapshot::query()->create([
        'snapshot_month' => '2026-03-01',
        'total_revenue' => 1210,
        'total_cash_collected' => 850,
        'total_payroll' => 441,
        'total_opex' => 340,
        'gross_profit' => 769,
        'net_profit' => 429,
        'burn_rate' => 170,
        'cash_runway_months' => 12,
        'headcount' => 12,
        'total_ar' => 405,
    ]);

    return [
        'admin' => $admin,
        'department' => $department,
        'employee' => $employee,
        'salaryMonth' => $salaryMonth,
        'loan' => $loan,
        'leave' => $leave,
        'attendance' => $attendance,
        'message' => $message,
        'holiday' => $holiday,
        'client' => $client,
        'project' => $project,
        'invoiceLinked' => $invoiceLinked,
        'invoicePartial' => $invoicePartial,
        'linkedPayment' => $linkedPayment,
        'advancePayment' => $advancePayment,
        'expensePayable' => $expensePayable,
        'expensePayablePayment' => $expensePayablePayment,
        'liability' => $liability,
        'asset' => $asset,
        'ownerEquity' => $ownerEquity,
        'snapshots' => [
            'jan' => $snapshotJan,
            'feb' => $snapshotFeb,
            'mar' => $snapshotMar,
        ],
    ];
}

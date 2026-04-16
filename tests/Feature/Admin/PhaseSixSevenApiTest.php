<?php

use App\Events\InsightStreamed;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Models\SalaryMonth;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can read and update general settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/settings/general')
        ->assertOk()
        ->assertJsonPath('general_settings.company_name', 'AuxFin');

    $payload = [
        'company_name' => 'AuxFin Labs',
        'company_email' => 'ops@auxfin.test',
        'currency' => 'USD',
        'timezone' => 'UTC',
        'available_cash' => 85000,
    ];

    $this->putJson('/api/admin/settings/general', $payload)
        ->assertOk()
        ->assertJsonPath('general_settings.company_name', 'AuxFin Labs')
        ->assertJsonPath('general_settings.available_cash', 85000);

    expect(Setting::getValue('general_settings', []))
        ->toMatchArray($payload);
});

test('admin can read and update tax policy settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/settings/tax-policy')
        ->assertOk()
        ->assertJsonPath('tax_policy.corporate_tax_rate', 30);

    $this->putJson('/api/admin/settings/tax-policy', [
        'corporate_tax_rate' => 27.5,
    ])
        ->assertOk()
        ->assertJsonPath('tax_policy.corporate_tax_rate', 27.5);

    expect((float) (Setting::getValue('tax_policy', [])['corporate_tax_rate'] ?? 0.0))
        ->toBe(27.5);
});

test('employee cannot access admin phase six seven endpoints', function () {
    $employeeUser = User::factory()->create(['role' => 'employee']);
    Sanctum::actingAs($employeeUser);

    $this->getJson('/api/admin/settings/general')->assertForbidden();
    $this->getJson('/api/admin/reports/profit-loss')->assertForbidden();
});

test('profit loss report returns rows and totals for selected range', function () {
    [$admin] = seedPhaseSixSevenData();
    Sanctum::actingAs($admin);

    $month = now()->startOfMonth()->toDateString();

    $response = $this->getJson("/api/admin/reports/profit-loss?from_month={$month}&to_month={$month}")
        ->assertOk()
        ->assertJsonStructure([
            'from',
            'to',
            'tax_rate_percent',
            'rows',
            'totals' => ['revenue', 'net_profit', 'estimated_tax', 'profit_after_tax'],
        ]);

    expect((float) $response->json('totals.revenue'))->toBeGreaterThan(0);
});

test('tax summary report uses configured corporate tax rate', function () {
    [$admin] = seedPhaseSixSevenData();
    Sanctum::actingAs($admin);

    Setting::query()->updateOrCreate(
        ['key' => 'tax_policy'],
        ['value' => ['corporate_tax_rate' => 25]]
    );

    $month = now()->startOfMonth()->toDateString();

    $response = $this->getJson("/api/admin/reports/tax-summary?from_month={$month}&to_month={$month}")
        ->assertOk()
        ->assertJsonPath('tax_rate_percent', 25);

    expect((float) $response->json('totals.corporate_tax_estimate'))->toBeGreaterThanOrEqual(0);
});

test('ar aging report returns distribution buckets and health score', function () {
    [$admin] = seedPhaseSixSevenData();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/reports/ar-aging')
        ->assertOk()
        ->assertJsonStructure([
            'as_of',
            'total_outstanding',
            'distribution' => ['0_30d', '31_60d', '61_90d', '90plus'],
            'health' => ['score', 'status'],
            'items',
        ]);

    expect((float) $response->json('total_outstanding'))->toBeGreaterThan(0);
});

test('accounting statement endpoints return accrual payloads', function () {
    [$admin] = seedPhaseSixSevenData();
    Sanctum::actingAs($admin);

    $asOf = now()->toDateString();
    $month = now()->startOfMonth()->toDateString();

    $this->getJson("/api/admin/reports/trial-balance?as_of={$asOf}")
        ->assertOk()
        ->assertJsonStructure([
            'as_of',
            'lines',
            'totals' => ['debit', 'credit', 'difference'],
            'is_balanced',
        ]);

    $this->getJson("/api/admin/reports/balance-sheet?as_of={$asOf}")
        ->assertOk()
        ->assertJsonStructure([
            'as_of',
            'assets',
            'liabilities',
            'equity',
            'totals' => ['assets', 'liabilities', 'equity', 'liabilities_plus_equity'],
            'is_balanced',
        ]);

    $this->getJson("/api/admin/reports/cash-flow?from_month={$month}&to_month={$month}")
        ->assertOk()
        ->assertJsonStructure([
            'from',
            'to',
            'rows',
            'totals' => ['cash_in_collections', 'operating_outflow', 'financing_outflow', 'net_cash_flow'],
        ]);

    $this->getJson('/api/admin/reports/general-ledger')
        ->assertOk()
        ->assertJsonStructure([
            'from',
            'to',
            'entries',
            'pagination' => ['page', 'per_page', 'total', 'last_page'],
            'summary' => ['total_debit', 'total_credit'],
        ]);

    $this->getJson('/api/admin/reports/payment-ledger')
        ->assertOk()
        ->assertJsonStructure([
            'from',
            'to',
            'entries',
            'pagination' => ['page', 'per_page', 'total', 'last_page'],
            'summary' => ['total_amount', 'payment_count', 'by_payment_method'],
        ]);
});

test('admin can record and delete project payments for invoices', function () {
    [$admin, $project, $paidInvoice, $partialInvoice] = seedPhaseSixSevenData();
    Sanctum::actingAs($admin);

    $createResponse = $this->postJson("/api/admin/projects/{$project->id}/payments", [
        'invoice_id' => $partialInvoice->id,
        'payment_date' => now()->toDateString(),
        'amount' => 1500,
        'payment_method' => 'bank_transfer',
        'reference_number' => 'TEST-PAY-001',
    ])->assertCreated();

    $paymentId = (int) $createResponse->json('payment.id');

    expect($paymentId)->toBeGreaterThan(0);

    $this->getJson("/api/admin/projects/{$project->id}/payments")
        ->assertOk()
        ->assertJsonPath('total', 3);

    $this->deleteJson("/api/admin/projects/{$project->id}/payments/{$paymentId}")
        ->assertOk();

    expect(ProjectPayment::query()->find($paymentId))->toBeNull();

    // A fully paid seeded invoice should stay paid.
    expect($paidInvoice->fresh()->status)->toBe('paid');
});

test('invoice status transitions create payment ledger and sync invoice totals', function () {
    [$admin, $project, $paidInvoice, $partialInvoice] = seedPhaseSixSevenData();
    Sanctum::actingAs($admin);

    $this->postJson("/api/admin/projects/{$project->id}/invoices/{$partialInvoice->id}/status", [
        'status' => 'partial',
        'partial_amount' => 1000,
    ])->assertOk();

    expect(
        ProjectPayment::query()
            ->where('project_id', $project->id)
            ->where('invoice_id', $partialInvoice->id)
            ->count()
    )->toBe(2);

    $this->postJson("/api/admin/projects/{$project->id}/invoices/{$partialInvoice->id}/status", [
        'status' => 'paid',
    ])->assertOk();

    $updatedInvoice = $partialInvoice->fresh();
    expect($updatedInvoice->status)->toBe('paid')
        ->and((float) $updatedInvoice->partial_amount)->toBe((float) $updatedInvoice->amount);

    // Seeded fully paid invoice remains consistent.
    expect($paidInvoice->fresh()->status)->toBe('paid');
});

test('report endpoints dispatch insight stream events', function () {
    [$admin] = seedPhaseSixSevenData();
    Sanctum::actingAs($admin);
    Event::fake([InsightStreamed::class]);

    $month = now()->startOfMonth()->toDateString();

    $this->getJson("/api/admin/reports/profit-loss?from_month={$month}&to_month={$month}")->assertOk();
    $this->getJson("/api/admin/reports/tax-summary?from_month={$month}&to_month={$month}")->assertOk();
    $this->getJson('/api/admin/reports/ar-aging')->assertOk();
    $this->getJson('/api/admin/reports/trial-balance')->assertOk();
    $this->getJson('/api/admin/reports/balance-sheet')->assertOk();
    $this->getJson('/api/admin/reports/cash-flow')->assertOk();
    $this->getJson('/api/admin/reports/general-ledger')->assertOk();
    $this->getJson('/api/admin/reports/payment-ledger')->assertOk();

    Event::assertDispatched(InsightStreamed::class, fn (InsightStreamed $event): bool => $event->stream === 'insight.report.profit_loss');
    Event::assertDispatched(InsightStreamed::class, fn (InsightStreamed $event): bool => $event->stream === 'insight.report.tax_summary');
    Event::assertDispatched(InsightStreamed::class, fn (InsightStreamed $event): bool => $event->stream === 'insight.report.ar_aging');
    Event::assertDispatched(InsightStreamed::class, fn (InsightStreamed $event): bool => $event->stream === 'insight.report.trial_balance');
    Event::assertDispatched(InsightStreamed::class, fn (InsightStreamed $event): bool => $event->stream === 'insight.report.balance_sheet');
    Event::assertDispatched(InsightStreamed::class, fn (InsightStreamed $event): bool => $event->stream === 'insight.report.cash_flow');
    Event::assertDispatched(InsightStreamed::class, fn (InsightStreamed $event): bool => $event->stream === 'insight.report.general_ledger');
    Event::assertDispatched(InsightStreamed::class, fn (InsightStreamed $event): bool => $event->stream === 'insight.report.payment_ledger');
});

test('security audit command dispatches insight stream event', function () {
    Event::fake([InsightStreamed::class]);

    $this->artisan('finerp:security:audit')
        ->assertExitCode(0);

    Event::assertDispatched(InsightStreamed::class, function (InsightStreamed $event): bool {
        return $event->stream === 'insight.security.audit'
            && ($event->payload['scope'] ?? null) === 'security';
    });
});

/**
 * @return array{0: User, 1: Project, 2: Invoice, 3: Invoice}
 */
function seedPhaseSixSevenData(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $monthStart = now()->startOfMonth();

    $client = Client::query()->create([
        'name' => 'Northwind Logistics',
        'email' => 'finance@northwind.test',
    ]);

    $project = Project::query()->create([
        'client_id' => $client->id,
        'name' => 'Quarterly Operations',
        'contract_amount' => 125000,
        'status' => 'active',
        'start_date' => $monthStart->copy()->subMonths(4)->toDateString(),
    ]);

    $paidInvoice = Invoice::query()->create([
        'project_id' => $project->id,
        'invoice_number' => 'INV-PAID-1001',
        'amount' => 42000,
        'invoice_date' => $monthStart->copy()->addDays(1)->toDateString(),
        'due_date' => $monthStart->copy()->addDays(5)->toDateString(),
        'status' => 'paid',
        'payment_completed_at' => $monthStart->copy()->addDays(8)->toDateTimeString(),
    ]);

    $partialInvoice = Invoice::query()->create([
        'project_id' => $project->id,
        'invoice_number' => 'INV-AR-1002',
        'amount' => 12000,
        'invoice_date' => $monthStart->copy()->addDays(2)->toDateString(),
        'partial_amount' => 2000,
        'due_date' => now()->subDays(20)->toDateString(),
        'status' => 'partial',
    ]);

    Invoice::query()->create([
        'project_id' => $project->id,
        'invoice_number' => 'INV-AR-1003',
        'amount' => 9000,
        'invoice_date' => $monthStart->copy()->addDays(3)->toDateString(),
        'due_date' => now()->subDays(45)->toDateString(),
        'status' => 'overdue',
    ]);

    Invoice::query()->create([
        'project_id' => $project->id,
        'invoice_number' => 'INV-AR-1004',
        'amount' => 7000,
        'invoice_date' => $monthStart->copy()->addDays(4)->toDateString(),
        'due_date' => now()->subDays(75)->toDateString(),
        'status' => 'overdue',
    ]);

    Invoice::query()->create([
        'project_id' => $project->id,
        'invoice_number' => 'INV-AR-1005',
        'amount' => 6500,
        'invoice_date' => $monthStart->copy()->addDays(5)->toDateString(),
        'due_date' => now()->subDays(120)->toDateString(),
        'status' => 'overdue',
    ]);

    ProjectPayment::query()->create([
        'project_id' => $project->id,
        'invoice_id' => $paidInvoice->id,
        'recorded_by' => $admin->id,
        'payment_date' => $monthStart->copy()->addDays(8)->toDateString(),
        'amount' => 42000,
        'payment_method' => 'bank_transfer',
        'reference_number' => 'SEED-PAID-1001',
    ]);

    ProjectPayment::query()->create([
        'project_id' => $project->id,
        'invoice_id' => $partialInvoice->id,
        'recorded_by' => $admin->id,
        'payment_date' => $monthStart->copy()->addDays(10)->toDateString(),
        'amount' => 2000,
        'payment_method' => 'bank_transfer',
        'reference_number' => 'SEED-PARTIAL-1002',
    ]);

    $employeeUser = User::factory()->create(['role' => 'employee']);

    $employee = Employee::query()->create([
        'user_id' => $employeeUser->id,
        'employee_code' => 'EMP-PHASE67',
        'designation' => 'Analyst',
        'date_of_joining' => now()->subYear()->toDateString(),
        'basic_salary' => 30000,
        'house_rent' => 12000,
        'conveyance' => 3000,
        'medical_allowance' => 2000,
        'pf_rate' => 8,
        'tds_rate' => 5,
        'professional_tax' => 200,
    ]);

    SalaryMonth::query()->create([
        'employee_id' => $employee->id,
        'month' => $monthStart->toDateString(),
        'gross_earnings' => 47000,
        'tds_deduction' => 2300,
        'pf_deduction' => 1500,
        'professional_tax' => 200,
        'total_deductions' => 4200,
        'net_payable' => 42800,
        'status' => 'processed',
        'processed_at' => now()->subDays(2)->toDateTimeString(),
        'processed_by' => $admin->id,
    ]);

    Expense::query()->create([
        'category' => 'Operations',
        'description' => 'Software and hosting',
        'amount' => 8500,
        'expense_date' => $monthStart->copy()->addDays(12)->toDateString(),
        'created_by' => $admin->id,
    ]);

    Liability::query()->create([
        'name' => 'Term Loan A',
        'principal_amount' => 90000,
        'outstanding' => 65000,
        'interest_rate' => 12,
        'monthly_payment' => 3000,
        'start_date' => now()->subMonths(6)->toDateString(),
        'end_date' => now()->addMonths(18)->toDateString(),
        'next_due_date' => now()->addDays(10)->toDateString(),
        'status' => 'active',
    ]);

    return [$admin, $project, $paidInvoice, $partialInvoice];
}

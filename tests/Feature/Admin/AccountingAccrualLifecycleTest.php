<?php

use App\Models\ExpensePayment;
use App\Models\OwnerEquityEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function accountingAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
    ]);
}

test('admin can perform owner equity CRUD lifecycle', function () {
    $admin = accountingAdmin();
    Sanctum::actingAs($admin);

    $create = $this->postJson('/api/admin/owner-equity', [
        'entry_date' => '2026-04-10',
        'entry_type' => 'capital_contribution',
        'amount' => 25000.50,
        'notes' => 'Initial owner capital injection',
    ])->assertCreated();

    $entryId = (int) $create->json('entry.id');
    expect($entryId)->toBeGreaterThan(0);

    $this->getJson('/api/admin/owner-equity?entry_type=capital_contribution&per_page=10')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.id', $entryId);

    $this->putJson("/api/admin/owner-equity/{$entryId}", [
        'entry_type' => 'drawing',
        'amount' => 3000,
        'notes' => 'Owner drawing for personal use',
    ])
        ->assertOk()
        ->assertJsonPath('entry.entry_type', 'drawing');

    $this->getJson("/api/admin/owner-equity/{$entryId}")
        ->assertOk()
        ->assertJsonPath('id', $entryId)
        ->assertJsonPath('entry_type', 'drawing');

    $this->deleteJson("/api/admin/owner-equity/{$entryId}")
        ->assertOk();

    expect(OwnerEquityEntry::query()->find($entryId))->toBeNull();
});

test('owner equity supports three-owner share split and owner-linked investment tracking', function () {
    $admin = accountingAdmin();
    Sanctum::actingAs($admin);

    $ownerA = $this->postJson('/api/admin/owner-equity/owners', [
        'name' => 'Owner A',
        'ownership_percentage' => 50,
        'initial_investment' => 100000,
    ])->assertCreated();

    $ownerB = $this->postJson('/api/admin/owner-equity/owners', [
        'name' => 'Owner B',
        'ownership_percentage' => 30,
        'initial_investment' => 60000,
    ])->assertCreated();

    $ownerC = $this->postJson('/api/admin/owner-equity/owners', [
        'name' => 'Owner C',
        'ownership_percentage' => 20,
        'initial_investment' => 40000,
    ])->assertCreated();

    $ownerAId = (int) $ownerA->json('owner.id');
    $ownerBId = (int) $ownerB->json('owner.id');
    $ownerCId = (int) $ownerC->json('owner.id');

    $this->postJson('/api/admin/owner-equity/owners', [
        'name' => 'Overflow Owner',
        'ownership_percentage' => 1,
        'initial_investment' => 1000,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('ownership_percentage');

    $this->postJson('/api/admin/owner-equity', [
        'entry_date' => '2026-04-10',
        'entry_type' => 'capital_contribution',
        'amount' => 5000,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('business_owner_id');

    $this->postJson('/api/admin/owner-equity', [
        'business_owner_id' => $ownerAId,
        'entry_date' => '2026-04-10',
        'entry_type' => 'capital_contribution',
        'amount' => 25000,
        'notes' => 'Owner A top-up',
    ])->assertCreated();

    $this->postJson('/api/admin/owner-equity', [
        'business_owner_id' => $ownerBId,
        'entry_date' => '2026-04-11',
        'entry_type' => 'capital_contribution',
        'amount' => 10000,
        'notes' => 'Owner B contribution',
    ])->assertCreated();

    $this->postJson('/api/admin/owner-equity', [
        'business_owner_id' => $ownerCId,
        'entry_date' => '2026-04-12',
        'entry_type' => 'drawing',
        'amount' => 5000,
        'notes' => 'Owner C drawing',
    ])->assertCreated();

    $ownersPayload = $this->getJson('/api/admin/owner-equity/owners')
        ->assertOk();

    expect((float) $ownersPayload->json('summary.total_active_ownership_percent'))->toBe(100.0);
    expect((bool) $ownersPayload->json('summary.is_fully_allocated'))->toBeTrue();
    expect((int) $ownersPayload->json('summary.owner_count'))->toBe(3);

    $owners = collect($ownersPayload->json('owners') ?? [])->keyBy('name');

    expect((float) data_get($owners, 'Owner A.capital_contributions', 0))->toBe(25000.0);
    expect((float) data_get($owners, 'Owner B.capital_contributions', 0))->toBe(10000.0);
    expect((float) data_get($owners, 'Owner C.drawings', 0))->toBe(5000.0);

    expect((float) data_get($owners, 'Owner A.net_investment', 0))->toBe(125000.0);
    expect((float) data_get($owners, 'Owner B.net_investment', 0))->toBe(70000.0);
    expect((float) data_get($owners, 'Owner C.net_investment', 0))->toBe(35000.0);

    $filtered = $this->getJson('/api/admin/owner-equity?business_owner_id='.$ownerAId.'&per_page=10')
        ->assertOk();

    expect((int) $filtered->json('total'))->toBe(1);
    expect((int) $filtered->json('data.0.business_owner_id'))->toBe($ownerAId);

    $this->deleteJson('/api/admin/owner-equity/owners/'.$ownerAId)
        ->assertStatus(422)
        ->assertJsonValidationErrors('owner_id');
});

test('admin can manage payable expense payment lifecycle including delete', function () {
    $admin = accountingAdmin();
    Sanctum::actingAs($admin);

    $expenseResponse = $this->postJson('/api/admin/expenses', [
        'category' => 'Vendor Services',
        'description' => 'Quarterly vendor support invoice',
        'amount' => 1000,
        'accounting_mode' => 'payable',
        'expense_date' => '2026-04-01',
        'payable_due_date' => '2026-04-30',
    ])->assertCreated();

    $expenseId = (int) $expenseResponse->json('expense.id');
    expect($expenseId)->toBeGreaterThan(0);

    $this->getJson("/api/admin/expenses/{$expenseId}/payments")
        ->assertOk()
        ->assertJsonPath('total_paid', 0);

    $firstPayment = $this->postJson("/api/admin/expenses/{$expenseId}/payments", [
        'amount' => 400,
        'payment_date' => '2026-04-05',
        'payment_method' => 'bank_transfer',
        'reference_number' => 'EXP-PAY-001',
    ])->assertCreated();

    $firstPaymentId = (int) $firstPayment->json('payment.id');

    $this->postJson("/api/admin/expenses/{$expenseId}/payments", [
        'amount' => 700,
        'payment_date' => '2026-04-06',
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Payment amount cannot exceed outstanding balance.');

    $secondPayment = $this->postJson("/api/admin/expenses/{$expenseId}/payments", [
        'amount' => 600,
        'payment_date' => '2026-04-07',
        'payment_method' => 'cash',
    ])->assertCreated();

    $secondPaymentId = (int) $secondPayment->json('payment.id');
    expect($secondPaymentId)->toBeGreaterThan($firstPaymentId);

    $paidSnapshot = $this->getJson("/api/admin/expenses/{$expenseId}")
        ->assertOk();

    expect((float) $paidSnapshot->json('paid_amount'))->toBe(1000.0);
    expect((float) $paidSnapshot->json('outstanding_amount'))->toBe(0.0);
    expect($paidSnapshot->json('payment_status'))->toBe('paid');

    $this->deleteJson("/api/admin/expenses/{$expenseId}/payments/{$firstPaymentId}")
        ->assertOk();

    $partialSnapshot = $this->getJson("/api/admin/expenses/{$expenseId}")
        ->assertOk();

    expect((float) $partialSnapshot->json('paid_amount'))->toBe(600.0);
    expect((float) $partialSnapshot->json('outstanding_amount'))->toBe(400.0);
    expect($partialSnapshot->json('payment_status'))->toBe('partial');

    $this->getJson("/api/admin/expenses/{$expenseId}/payments")
        ->assertOk()
        ->assertJsonCount(1, 'rows')
        ->assertJsonPath('total_paid', 600);

    $this->deleteJson("/api/admin/expenses/{$expenseId}")
        ->assertOk();

    expect(ExpensePayment::query()->where('expense_id', $expenseId)->count())->toBe(0);
});

test('prepaid amortization handles deferred start and full allocation edge cases', function () {
    $admin = accountingAdmin();
    Sanctum::actingAs($admin);

    $this->postJson('/api/admin/expenses', [
        'category' => 'Deferred Insurance',
        'description' => 'Policy starts in April',
        'amount' => 600,
        'accounting_mode' => 'prepaid',
        'expense_date' => '2026-01-05',
        'prepaid_start_date' => '2026-04-01',
        'prepaid_months' => 6,
    ])->assertCreated();

    $this->postJson('/api/admin/expenses', [
        'category' => 'Three-month software prepaid',
        'description' => 'Non-even monthly allocation case',
        'amount' => 1000,
        'accounting_mode' => 'prepaid',
        'expense_date' => '2026-01-10',
        'prepaid_start_date' => '2026-01-01',
        'prepaid_months' => 3,
    ])->assertCreated();

    Cache::flush();

    $marchBalance = $this->getJson('/api/admin/reports/balance-sheet?as_of=2026-03-31')
        ->assertOk();

    expect((float) $marchBalance->json('assets.prepaid_expenses'))->toBe(600.0);

    $aprilBalance = $this->getJson('/api/admin/reports/balance-sheet?as_of=2026-04-30')
        ->assertOk();

    expect((float) $aprilBalance->json('assets.prepaid_expenses'))->toBe(500.0);

    $marchProfit = $this->getJson('/api/admin/reports/profit-loss?from_month=2026-03-01&to_month=2026-03-01')
        ->assertOk();
    $marchOpex = (float) data_get($marchProfit->json(), 'rows.0.opex', 0);

    expect(abs($marchOpex - 333.33))->toBeLessThanOrEqual(0.01);

    $aprilProfit = $this->getJson('/api/admin/reports/profit-loss?from_month=2026-04-01&to_month=2026-04-01')
        ->assertOk();
    $aprilOpex = (float) data_get($aprilProfit->json(), 'rows.0.opex', 0);

    expect(abs($aprilOpex - 100.0))->toBeLessThanOrEqual(0.01);

    $ledger = $this->getJson('/api/admin/reports/general-ledger?from_date=2026-01-01&to_date=2026-04-30&per_page=200')
        ->assertOk();

    $amortizationEntries = collect($ledger->json('entries') ?? [])
        ->where('entry_type', 'prepaid_amortization')
        ->values();

    expect($amortizationEntries->count())->toBe(4);
    expect(round((float) $amortizationEntries->sum('amount'), 2))->toBe(1100.0);
});

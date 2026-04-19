<?php

use App\Models\SalaryMonth;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
});

test('admin frontend endpoints return populated seeded data', function () {
    $admin = User::query()->where('email', 'admin@finerp.local')->firstOrFail();
    Sanctum::actingAs($admin, ['admin']);

    $employees = $this->getJson('/api/admin/employees')->assertOk()->json('data');
    expect(count($employees))->toBeGreaterThan(0);

    $payroll = $this->getJson('/api/admin/payroll/'.now()->startOfMonth()->toDateString())
        ->assertOk()
        ->json();
    expect(count($payroll))->toBeGreaterThan(0);

    $messages = $this->getJson('/api/admin/messages')->assertOk()->json('data');
    expect(count($messages))->toBeGreaterThan(0);

    $finance = $this->getJson('/api/admin/finance/overview')->assertOk()->json();
    expect((float) ($finance['kpis']['booked_revenue'] ?? 0))->toBeGreaterThan(0)
        ->and((float) ($finance['kpis']['accounts_receivable'] ?? 0))->toBeGreaterThanOrEqual(0)
        ->and((int) ($finance['kpis']['projects'] ?? 0))->toBeGreaterThan(0);

    $analytics = $this->getJson('/api/admin/analytics/overview')->assertOk()->json();
    expect(count($analytics['series'] ?? []))->toBeGreaterThan(0);

    $growth = $this->getJson('/api/admin/analytics/growth')->assertOk()->json();
    expect(count($growth['series'] ?? []))->toBeGreaterThan(0);
});

test('employee frontend endpoints return populated seeded data', function () {
    $employeeUser = User::query()->where('email', 'sadia@finerp.local')->firstOrFail();
    Sanctum::actingAs($employeeUser, ['employee']);

    $dashboard = $this->getJson('/api/employee/dashboard')->assertOk()->json();
    expect((float) ($dashboard['current_month_net_salary'] ?? 0))->toBeGreaterThan(0)
        ->and((float) ($dashboard['total_earned_ytd'] ?? 0))->toBeGreaterThan(0);

    $salary = $this->getJson('/api/employee/salary')->assertOk()->json('data');
    expect(count($salary))->toBeGreaterThan(0);

    $attendance = $this->getJson('/api/employee/attendance')->assertOk()->json();
    expect(count($attendance['records'] ?? []))->toBeGreaterThan(0);

    $leavesPayload = $this->getJson('/api/employee/leaves')->assertOk()->json();
    $leaves = is_array($leavesPayload['data'] ?? null) ? $leavesPayload['data'] : $leavesPayload;
    expect(count($leaves))->toBeGreaterThan(0);

    $messages = $this->getJson('/api/employee/messages')->assertOk()->json('data');
    expect(count($messages))->toBeGreaterThan(0);
});

test('seeded payroll rows remain calculation-consistent', function () {
    $rows = SalaryMonth::query()->get();

    expect($rows->count())->toBeGreaterThan(0);

    foreach ($rows as $row) {
        $gross = (float) $row->gross_earnings;
        $deductions = (float) $row->total_deductions;
        $net = (float) $row->net_payable;

        expect(round($gross - $deductions, 2))->toBe(round($net, 2));
    }
});

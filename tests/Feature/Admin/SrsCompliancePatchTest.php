<?php

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeMessage;
use App\Models\SalaryMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

function makeEmployee(string $email = 'emp@test.local'): Employee
{
    $user = User::factory()->create([
        'email' => $email,
        'role' => 'employee',
        'passkey' => 'Pass#1234',
    ]);

    return Employee::query()->create([
        'user_id' => $user->id,
        'employee_code' => strtoupper(str_replace(['@', '.'], '', substr($email, 0, 8))).'-01',
        'designation' => 'Executive',
        'date_of_joining' => now()->subYear()->toDateString(),
        'basic_salary' => 30000,
        'house_rent' => 10000,
        'conveyance' => 3000,
        'medical_allowance' => 2000,
        'pf_rate' => 10,
        'tds_rate' => 5,
        'professional_tax' => 300,
    ]);
}

test('login issues sanctum token with explicit expiry', function () {
    config(['sanctum.expiration' => 480]);

    $user = User::factory()->create([
        'email' => 'login@test.local',
        'role' => 'employee',
        'passkey' => 'MyPass#12',
    ]);

    Employee::query()->create([
        'user_id' => $user->id,
        'employee_code' => 'EMP-4001',
        'designation' => 'Analyst',
        'date_of_joining' => now()->subMonths(8)->toDateString(),
        'basic_salary' => 25000,
        'house_rent' => 9000,
        'conveyance' => 2500,
        'medical_allowance' => 1500,
        'pf_rate' => 10,
        'tds_rate' => 5,
        'professional_tax' => 200,
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'login@test.local',
        'passkey' => 'MyPass#12',
    ])->assertOk();

    $token = PersonalAccessToken::query()->latest('id')->first();

    expect($token)->not->toBeNull()
        ->and($token->expires_at)->not->toBeNull();

    $expiresDiff = now()->diffInMinutes($token->expires_at, false);
    expect($expiresDiff)->toBeGreaterThanOrEqual(470)
        ->toBeLessThanOrEqual(490);
});

test('admin can manage departments through api resource', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin, ['admin']);

    $create = $this->postJson('/api/admin/departments', [
        'name' => 'Finance Operations',
    ])->assertCreated();

    $departmentId = $create->json('department.id');

    $this->getJson('/api/admin/departments')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Finance Operations']);

    $this->putJson("/api/admin/departments/{$departmentId}", [
        'name' => 'Finance & Operations',
    ])->assertOk()->assertJsonPath('department.name', 'Finance & Operations');

    $this->deleteJson("/api/admin/departments/{$departmentId}")
        ->assertOk();

    expect(Department::query()->find($departmentId))->toBeNull();
});

test('employee show response masks bank account fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin, ['admin']);

    $employee = makeEmployee('masked@test.local');
    $employee->update([
        'bank_name' => 'Trust Bank',
        'bank_account_number' => '123456789012',
    ]);

    $response = $this->getJson("/api/admin/employees/{$employee->id}")
        ->assertOk();

    expect($response->json('bank_account_number'))->toBeNull()
        ->and($response->json('masked_bank_account'))->toBe('********9012');
});

test('mark excused recalculates late penalty for non paid salary and creates audit log', function () {
    Event::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin, ['admin']);

    $employee = makeEmployee('lateappeal@test.local');

    $month = now()->startOfMonth()->toDateString();
    $dateOne = now()->startOfMonth()->addDay()->toDateString();
    $dateTwo = now()->startOfMonth()->addDays(2)->toDateString();

    Attendance::query()->create([
        'employee_id' => $employee->id,
        'date' => $dateOne,
        'status' => 'late',
        'is_late' => true,
    ]);

    Attendance::query()->create([
        'employee_id' => $employee->id,
        'date' => $dateTwo,
        'status' => 'late',
        'is_late' => true,
    ]);

    SalaryMonth::query()->create([
        'employee_id' => $employee->id,
        'month' => $month,
        'gross_earnings' => 20000,
        'tds_deduction' => 1000,
        'pf_deduction' => 1000,
        'professional_tax' => 100,
        'unpaid_leave_deduction' => 0,
        'loan_emi_deduction' => 0,
        'late_penalty_deduction' => 1000,
        'total_deductions' => 3100,
        'net_payable' => 16900,
        'late_entries' => 2,
        'expected_working_days' => 20,
        'status' => 'processed',
    ]);

    $message = EmployeeMessage::query()->create([
        'employee_id' => $employee->id,
        'type' => 'late_appeal',
        'subject' => 'Please excuse one late day',
        'body' => 'Traffic disruption due to accident',
        'reference_date' => $dateOne,
        'reference_month' => $month,
        'status' => 'open',
        'priority' => 'normal',
    ]);

    $this->postJson("/api/admin/messages/{$message->id}/reply", [
        'admin_reply' => 'Accepted and excused.',
        'action_taken' => 'mark_excused',
        'status' => 'resolved',
    ])->assertOk();

    $salary = SalaryMonth::query()->where('employee_id', $employee->id)->whereDate('month', $month)->first();

    expect((int) $salary->late_entries)->toBe(1)
        ->and((float) $salary->late_penalty_deduction)->toBe(0.0)
        ->and((float) $salary->total_deductions)->toBe(2100.0)
        ->and((float) $salary->net_payable)->toBe(17900.0);

    expect(AuditLog::query()->where('action', 'late.excused')->exists())->toBeTrue();
});

test('deduction reversed cannot modify paid salary month', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin, ['admin']);

    $employee = makeEmployee('paidmonth@test.local');

    $month = now()->startOfMonth()->toDateString();

    SalaryMonth::query()->create([
        'employee_id' => $employee->id,
        'month' => $month,
        'gross_earnings' => 25000,
        'tds_deduction' => 1000,
        'pf_deduction' => 1000,
        'professional_tax' => 100,
        'unpaid_leave_deduction' => 0,
        'loan_emi_deduction' => 0,
        'late_penalty_deduction' => 1200,
        'total_deductions' => 3300,
        'net_payable' => 21700,
        'late_entries' => 3,
        'expected_working_days' => 22,
        'status' => 'paid',
    ]);

    $message = EmployeeMessage::query()->create([
        'employee_id' => $employee->id,
        'type' => 'deduction_dispute',
        'subject' => 'Please reverse late deduction',
        'body' => 'Paid month dispute',
        'reference_month' => $month,
        'status' => 'open',
        'priority' => 'normal',
    ]);

    $this->postJson("/api/admin/messages/{$message->id}/reply", [
        'admin_reply' => 'Attempting reverse',
        'action_taken' => 'deduction_reversed',
        'status' => 'under_review',
    ])->assertStatus(422);

    $salary = SalaryMonth::query()->where('employee_id', $employee->id)->whereDate('month', $month)->first();
    expect((float) $salary->late_penalty_deduction)->toBe(1200.0)
        ->and((float) $salary->net_payable)->toBe(21700.0);
});

<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Setting;
use App\Models\User;
use App\Services\LoanService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function employeeFixture(string $email = 'calc@test.local'): Employee
{
    $user = User::factory()->create([
        'email' => $email,
        'role' => 'employee',
        'passkey' => 'Calc#2026',
    ]);

    return Employee::query()->create([
        'user_id' => $user->id,
        'employee_code' => 'EMP-CALC-'.substr((string) $user->id, -2),
        'designation' => 'Analyst',
        'date_of_joining' => now()->subYear()->toDateString(),
        'basic_salary' => 30000,
        'house_rent' => 10000,
        'conveyance' => 3000,
        'medical_allowance' => 2000,
        'pf_rate' => 10,
        'tds_rate' => 5,
        'professional_tax' => 200,
        'working_days_per_week' => 6,
        'weekly_off_days' => ['friday'],
    ]);
}

test('payroll computation stays formula-consistent with late, leave and emi deductions', function () {
    $employee = employeeFixture('formula@test.local');

    $month = now()->startOfMonth()->toDateString();

    Setting::query()->updateOrCreate(
        ['key' => 'late_policy'],
        ['value' => [
            'late_days_per_unit' => 2,
            'deduction_unit_type' => 'full_day',
            'grace_period_minutes' => 15,
            'office_start_time' => '09:00',
            'carry_forward' => false,
        ]]
    );

    Loan::query()->create([
        'employee_id' => $employee->id,
        'loan_reference' => 'LON-CALC-1001',
        'amount_requested' => 12000,
        'amount_approved' => 12000,
        'repayment_months' => 12,
        'emi_amount' => 1000,
        'start_month' => $month,
        'reason' => 'Emergency support',
        'status' => 'approved',
        'amount_remaining' => 12000,
    ]);

    $lateDates = [
        now()->startOfMonth()->addDay(),
        now()->startOfMonth()->addDays(4),
        now()->startOfMonth()->addDays(8),
    ];

    foreach ($lateDates as $date) {
        Attendance::query()->create([
            'employee_id' => $employee->id,
            'date' => $date->toDateString(),
            'status' => 'late',
            'is_late' => true,
        ]);
    }

    Leave::query()->create([
        'employee_id' => $employee->id,
        'leave_type' => 'unpaid',
        'from_date' => now()->startOfMonth()->addDays(10)->toDateString(),
        'to_date' => now()->startOfMonth()->addDays(11)->toDateString(),
        'days' => 2,
        'reason' => 'Unpaid leave sample',
        'status' => 'approved',
    ]);

    /** @var PayrollService $payroll */
    $payroll = app(PayrollService::class);

    $salary = $payroll->processMonth($employee, $month);

    $gross = (float) $salary->gross_earnings;
    $expectedDays = max(1, (int) $salary->expected_working_days);
    $dailyRate = $gross / $expectedDays;

    $expectedLatePenalty = round($dailyRate * floor(3 / 2), 2);
    $expectedLeaveDeduction = round($dailyRate * 2, 2);

    expect((float) $salary->late_penalty_deduction)->toBe($expectedLatePenalty)
        ->and((float) $salary->unpaid_leave_deduction)->toBe($expectedLeaveDeduction)
        ->and((float) $salary->loan_emi_deduction)->toBe(1000.0);

    $deductionsSum = round(
        (float) $salary->tds_deduction
        + (float) $salary->pf_deduction
        + (float) $salary->professional_tax
        + (float) $salary->unpaid_leave_deduction
        + (float) $salary->late_penalty_deduction
        + (float) $salary->loan_emi_deduction,
        2
    );

    expect((float) $salary->total_deductions)->toBe($deductionsSum)
        ->and((float) $salary->net_payable)->toBe(round($gross - $deductionsSum, 2));
});

test('loan monthly deduction creates repayment record and transitions to completed when fully paid', function () {
    $employee = employeeFixture('loan-complete@test.local');
    $month = now()->startOfMonth()->toDateString();

    $loan = Loan::query()->create([
        'employee_id' => $employee->id,
        'loan_reference' => 'LON-COMPLETE-1',
        'amount_requested' => 700,
        'amount_approved' => 700,
        'repayment_months' => 1,
        'emi_amount' => 1000,
        'start_month' => $month,
        'reason' => 'Short recovery',
        'status' => 'active',
        'amount_remaining' => 700,
    ]);

    /** @var LoanService $loanService */
    $loanService = app(LoanService::class);
    $loanService->applyMonthlyDeduction($employee, $month);

    $loan->refresh();

    expect((float) $loan->amount_remaining)->toBe(0.0)
        ->and($loan->status)->toBe('completed');

    $repayment = LoanRepayment::query()->where('loan_id', $loan->id)->whereDate('month', $month)->first();

    expect($repayment)->not->toBeNull()
        ->and((float) $repayment->amount_paid)->toBe(700.0);
});

<?php

declare(strict_types=1);

use App\Models\Asset;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\CompanySnapshot;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeMessage;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Liability;
use App\Models\Loan;
use App\Models\SalaryMonth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

function qaLog(string $line): void
{
    echo $line.PHP_EOL;
}

function qaAssert(bool $condition, string $caseId, string $message, ?string $raw = null): void
{
    if (! $condition) {
        $detail = $raw ? ' | raw='.substr($raw, 0, 260) : '';
        throw new RuntimeException("[{$caseId}] {$message}{$detail}");
    }
}

/**
 * @return array{status:int,json:array<string,mixed>|null,raw:string}
 */
function qaApi(string $method, string $uri, ?string $token = null, array $payload = []): array
{
    $server = [
        'HTTP_ACCEPT' => 'application/json',
    ];

    if ($token) {
        $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
    }

    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $server['CONTENT_TYPE'] = 'application/json';
        $request = Request::create(
            $uri,
            $method,
            [],
            [],
            [],
            $server,
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    } else {
        $request = Request::create($uri, $method, $payload, [], [], $server);
    }

    $response = app()->handle($request);
    $raw = $response->getContent();
    $decoded = json_decode($raw, true);

    return [
        'status' => $response->getStatusCode(),
        'json' => is_array($decoded) ? $decoded : null,
        'raw' => $raw,
    ];
}

/**
 * @param list<array<string,mixed>> $rows
 */
function qaFind(array $rows, callable $predicate): ?array
{
    foreach ($rows as $row) {
        if ($predicate($row)) {
            return $row;
        }
    }

    return null;
}

qaLog('=== FinERP Production Matrix Runner (Tinker) ===');

$monthStart = Carbon::now()->startOfMonth();
$monthDate = $monthStart->toDateString();
$previousMonthDate = $monthStart->copy()->subMonth()->toDateString();
$qaHolidayDate = $monthStart->copy()->addDays(16)->toDateString();
$qaStamp = Carbon::now()->format('Ymd');

$adminUser = User::query()->where('email', 'admin@finerp.local')->firstOrFail();
$sadiaUser = User::query()->where('email', 'sadia@finerp.local')->firstOrFail();
$sadiaEmployee = $sadiaUser->employee;
qaAssert($sadiaEmployee !== null, 'BOOT-001', 'Seeded employee Sadia must exist.');

$qaDepartment = Department::query()->updateOrCreate(
    ['name' => 'QA Matrix Department'],
    ['head_id' => $sadiaEmployee->id]
);

$qaUser = User::query()->updateOrCreate(
    ['email' => 'qa.matrix.employee@finerp.local'],
    [
        'name' => 'QA Matrix Employee',
        'passkey' => 'QAMatrix#2026',
        'role' => 'employee',
        'is_active' => true,
        'created_by' => $adminUser->id,
    ]
);

$qaEmployee = Employee::query()->updateOrCreate(
    ['user_id' => $qaUser->id],
    [
        'employee_code' => 'EMP-QA-9001',
        'department_id' => $qaDepartment->id,
        'designation' => 'QA Validation Analyst',
        'date_of_joining' => '2024-01-15',
        'bank_account_number' => '987654321',
        'bank_name' => 'QA Trust Bank',
        'basic_salary' => 60000,
        'house_rent' => 20000,
        'conveyance' => 5000,
        'medical_allowance' => 4000,
        'pf_rate' => 10,
        'tds_rate' => 5,
        'professional_tax' => 500,
        'late_threshold_days' => 3,
        'late_penalty_type' => 'full_day',
        'working_days_per_week' => 5,
        'weekly_off_days' => ['friday', 'saturday'],
    ]
);

$qaAttendanceLate = Attendance::query()->updateOrCreate(
    ['employee_id' => $qaEmployee->id, 'date' => $monthStart->copy()->addDays(2)->toDateString()],
    ['status' => 'late', 'check_in' => '09:45', 'check_out' => '18:00', 'is_late' => true, 'late_minutes' => 45]
);
Attendance::query()->updateOrCreate(
    ['employee_id' => $qaEmployee->id, 'date' => $monthStart->copy()->addDays(3)->toDateString()],
    ['status' => 'present', 'check_in' => '09:05', 'check_out' => '18:00', 'is_late' => false, 'late_minutes' => 0]
);
Attendance::query()->updateOrCreate(
    ['employee_id' => $qaEmployee->id, 'date' => $monthStart->copy()->addDays(4)->toDateString()],
    ['status' => 'absent', 'check_in' => null, 'check_out' => null, 'is_late' => false, 'late_minutes' => 0]
);
Attendance::query()->updateOrCreate(
    ['employee_id' => $sadiaEmployee->id, 'date' => $monthStart->copy()->addDays(2)->toDateString()],
    ['status' => 'present', 'check_in' => '09:00', 'check_out' => '18:00', 'is_late' => false, 'late_minutes' => 0]
);

$qaLeavePending = Leave::query()->updateOrCreate(
    [
        'employee_id' => $qaEmployee->id,
        'from_date' => $monthStart->copy()->addDays(10)->toDateString(),
        'to_date' => $monthStart->copy()->addDays(11)->toDateString(),
    ],
    [
        'leave_type' => 'casual',
        'days' => 2,
        'reason' => 'QA pending leave',
        'status' => 'pending',
        'admin_note' => null,
        'reviewed_by' => null,
        'reviewed_at' => null,
    ]
);

$qaLeaveApproved = Leave::query()->updateOrCreate(
    [
        'employee_id' => $qaEmployee->id,
        'from_date' => $monthStart->copy()->addDays(12)->toDateString(),
        'to_date' => $monthStart->copy()->addDays(12)->toDateString(),
    ],
    [
        'leave_type' => 'unpaid',
        'days' => 1,
        'reason' => 'QA approved leave',
        'status' => 'approved',
        'admin_note' => 'Approved for edge case.',
        'reviewed_by' => $adminUser->id,
        'reviewed_at' => now(),
    ]
);

Leave::query()->updateOrCreate(
    [
        'employee_id' => $sadiaEmployee->id,
        'from_date' => $monthStart->copy()->addDays(20)->toDateString(),
        'to_date' => $monthStart->copy()->addDays(20)->toDateString(),
    ],
    [
        'leave_type' => 'sick',
        'days' => 1,
        'reason' => 'Sadia sick day QA',
        'status' => 'pending',
    ]
);

$qaLoanPending = Loan::query()->updateOrCreate(
    ['loan_reference' => 'LON-QA-PEND-'.$qaStamp],
    [
        'employee_id' => $qaEmployee->id,
        'amount_requested' => 15000,
        'reason' => 'QA pending loan',
        'status' => 'pending',
        'amount_remaining' => null,
    ]
);

$qaLoanApproved = Loan::query()->updateOrCreate(
    ['loan_reference' => 'LON-QA-APPR-'.$qaStamp],
    [
        'employee_id' => $qaEmployee->id,
        'amount_requested' => 20000,
        'amount_approved' => 20000,
        'repayment_months' => 10,
        'emi_amount' => 2000,
        'start_month' => $monthDate,
        'reason' => 'QA approved loan',
        'status' => 'approved',
        'amount_remaining' => 16000,
        'reviewed_by' => $adminUser->id,
        'reviewed_at' => now(),
    ]
);

Loan::query()->updateOrCreate(
    ['loan_reference' => 'LON-SADIA-QA-'.$qaStamp],
    [
        'employee_id' => $sadiaEmployee->id,
        'amount_requested' => 18000,
        'amount_approved' => 18000,
        'repayment_months' => 12,
        'emi_amount' => 1500,
        'start_month' => $monthDate,
        'reason' => 'Sadia QA loan',
        'status' => 'active',
        'amount_remaining' => 16500,
        'reviewed_by' => $adminUser->id,
        'reviewed_at' => now(),
    ]
);

$qaSalaryCurrent = SalaryMonth::query()->updateOrCreate(
    ['employee_id' => $qaEmployee->id, 'month' => $monthDate],
    [
        'basic_salary' => 60000,
        'house_rent' => 20000,
        'conveyance' => 5000,
        'medical_allowance' => 4000,
        'performance_bonus' => 2000,
        'festival_bonus' => 0,
        'overtime_pay' => 0,
        'other_bonus' => 0,
        'gross_earnings' => 91000,
        'tds_deduction' => 4550,
        'pf_deduction' => 6000,
        'professional_tax' => 500,
        'unpaid_leave_deduction' => 1000,
        'late_penalty_deduction' => 0,
        'loan_emi_deduction' => 0,
        'total_deductions' => 12050,
        'net_payable' => 78950,
        'days_present' => 2,
        'unpaid_leave_days' => 1,
        'late_entries' => 1,
        'expected_working_days' => 20,
        'status' => 'processed',
        'processed_at' => now(),
        'processed_by' => $adminUser->id,
    ]
);

$qaSalaryPaid = SalaryMonth::query()->updateOrCreate(
    ['employee_id' => $qaEmployee->id, 'month' => $previousMonthDate],
    [
        'basic_salary' => 60000,
        'house_rent' => 20000,
        'conveyance' => 5000,
        'medical_allowance' => 4000,
        'performance_bonus' => 0,
        'festival_bonus' => 0,
        'overtime_pay' => 0,
        'other_bonus' => 0,
        'gross_earnings' => 89000,
        'tds_deduction' => 4450,
        'pf_deduction' => 6000,
        'professional_tax' => 500,
        'unpaid_leave_deduction' => 0,
        'late_penalty_deduction' => 0,
        'loan_emi_deduction' => 0,
        'total_deductions' => 10950,
        'net_payable' => 78050,
        'days_present' => 20,
        'unpaid_leave_days' => 0,
        'late_entries' => 0,
        'expected_working_days' => 20,
        'status' => 'paid',
        'processed_at' => now()->subMonth(),
        'paid_at' => now()->subMonth(),
        'processed_by' => $adminUser->id,
    ]
);

SalaryMonth::query()->updateOrCreate(
    ['employee_id' => $sadiaEmployee->id, 'month' => $monthDate],
    [
        'basic_salary' => 52000,
        'house_rent' => 18000,
        'conveyance' => 5000,
        'medical_allowance' => 3500,
        'performance_bonus' => 1000,
        'festival_bonus' => 0,
        'overtime_pay' => 0,
        'other_bonus' => 0,
        'gross_earnings' => 79500,
        'tds_deduction' => 3975,
        'pf_deduction' => 5200,
        'professional_tax' => 500,
        'unpaid_leave_deduction' => 0,
        'late_penalty_deduction' => 0,
        'loan_emi_deduction' => 1500,
        'total_deductions' => 11175,
        'net_payable' => 68325,
        'days_present' => 20,
        'unpaid_leave_days' => 0,
        'late_entries' => 0,
        'expected_working_days' => 20,
        'status' => 'processed',
        'processed_at' => now(),
        'processed_by' => $adminUser->id,
    ]
);

$qaAdminMessage = EmployeeMessage::query()->updateOrCreate(
    ['employee_id' => $qaEmployee->id, 'subject' => 'QA Matrix Admin Message'],
    [
        'type' => 'general_hr',
        'body' => 'Admin message seeded for matrix verification.',
        'status' => 'open',
        'priority' => 'normal',
        'action_taken' => 'none',
    ]
);

$qaEmployeeMessage = EmployeeMessage::query()->updateOrCreate(
    ['employee_id' => $sadiaEmployee->id, 'subject' => 'QA Matrix Employee Inbox'],
    [
        'type' => 'salary_query',
        'body' => 'Employee inbox message for matrix verification.',
        'status' => 'open',
        'priority' => 'normal',
        'action_taken' => 'none',
    ]
);

$qaClient = Client::query()->updateOrCreate(
    ['name' => 'QA Matrix Client'],
    [
        'email' => 'qa.client@finerp.local',
        'phone' => '+8801700000000',
        'contact_person' => 'QA Contact',
        'address' => 'Dhaka',
    ]
);

$qaProject = App\Models\Project::query()->updateOrCreate(
    ['client_id' => $qaClient->id, 'name' => 'QA Matrix Project'],
    [
        'description' => 'Project for matrix verification',
        'contract_amount' => 250000,
        'status' => 'active',
        'start_date' => $monthStart->copy()->subMonth()->toDateString(),
        'end_date' => null,
    ]
);

$qaInvoicePaid = Invoice::query()->updateOrCreate(
    ['invoice_number' => 'INV-QA-PAID-'.$qaStamp],
    [
        'project_id' => $qaProject->id,
        'amount' => 90000,
        'due_date' => $monthStart->copy()->addDays(8)->toDateString(),
        'status' => 'paid',
        'partial_amount' => 90000,
        'payment_completed_at' => now(),
        'notes' => 'Paid invoice for QA matrix',
    ]
);

$qaInvoicePartial = Invoice::query()->updateOrCreate(
    ['invoice_number' => 'INV-QA-PART-'.$qaStamp],
    [
        'project_id' => $qaProject->id,
        'amount' => 60000,
        'due_date' => $monthStart->copy()->subDays(20)->toDateString(),
        'status' => 'partial',
        'partial_amount' => 15000,
        'payment_completed_at' => null,
        'notes' => 'Partial invoice for QA matrix',
    ]
);

Invoice::query()->updateOrCreate(
    ['invoice_number' => 'INV-QA-OVD-'.$qaStamp],
    [
        'project_id' => $qaProject->id,
        'amount' => 30000,
        'due_date' => $monthStart->copy()->subDays(95)->toDateString(),
        'status' => 'overdue',
        'partial_amount' => null,
        'payment_completed_at' => null,
        'notes' => 'Overdue invoice for QA matrix',
    ]
);

Expense::query()->updateOrCreate(
    ['description' => 'QA Matrix Recurring Infra'],
    [
        'category' => 'Infrastructure',
        'amount' => 22000,
        'expense_date' => $monthDate,
        'is_recurring' => true,
        'recurrence' => 'monthly',
        'next_due_date' => $monthStart->copy()->addMonth()->toDateString(),
        'created_by' => $adminUser->id,
    ]
);

Expense::query()->updateOrCreate(
    ['description' => 'QA Matrix One-time Audit'],
    [
        'category' => 'Compliance',
        'amount' => 8000,
        'expense_date' => $monthDate,
        'is_recurring' => false,
        'recurrence' => null,
        'next_due_date' => null,
        'created_by' => $adminUser->id,
    ]
);

$qaLiability = Liability::query()->updateOrCreate(
    ['name' => 'QA Matrix Liability'],
    [
        'principal_amount' => 120000,
        'outstanding' => 90000,
        'interest_rate' => 10,
        'monthly_payment' => 10000,
        'start_date' => $monthStart->copy()->subMonths(2)->toDateString(),
        'end_date' => $monthStart->copy()->addMonths(10)->toDateString(),
        'next_due_date' => now()->addDays(5)->toDateString(),
        'status' => 'active',
    ]
);

$qaAsset = Asset::query()->updateOrCreate(
    ['name' => 'QA Matrix Laptop Fleet'],
    [
        'category' => 'IT Equipment',
        'purchase_date' => $monthStart->copy()->subMonths(4)->toDateString(),
        'purchase_cost' => 240000,
        'current_book_value' => 200000,
        'useful_life_months' => 48,
        'monthly_depreciation' => 5000,
        'status' => 'active',
    ]
);

CompanySnapshot::query()->updateOrCreate(
    ['snapshot_month' => $monthStart->copy()->subMonth()->toDateString()],
    [
        'total_revenue' => 180000,
        'total_payroll' => 90000,
        'total_opex' => 35000,
        'gross_profit' => 90000,
        'net_profit' => 55000,
        'burn_rate' => 125000,
        'cash_runway_months' => 4,
        'headcount' => Employee::query()->count(),
        'total_ar' => 45000,
    ]
);

CompanySnapshot::query()->updateOrCreate(
    ['snapshot_month' => $monthDate],
    [
        'total_revenue' => 220000,
        'total_payroll' => 98000,
        'total_opex' => 40000,
        'gross_profit' => 122000,
        'net_profit' => 82000,
        'burn_rate' => 138000,
        'cash_runway_months' => 4,
        'headcount' => Employee::query()->count(),
        'total_ar' => 75000,
    ]
);

qaLog('Fixtures prepared successfully.');

$adminLogin = qaApi('POST', '/api/auth/login', null, [
    'email' => 'admin@finerp.local',
    'passkey' => 'Admin#2026',
]);
qaAssert($adminLogin['status'] === 200, 'AUTH-001', 'Admin login failed.', $adminLogin['raw']);
qaAssert(! empty($adminLogin['json']['token'] ?? null), 'AUTH-001', 'Admin token missing.', $adminLogin['raw']);
$adminToken = (string) $adminLogin['json']['token'];

$employeeLogin = qaApi('POST', '/api/auth/login', null, [
    'email' => 'sadia@finerp.local',
    'passkey' => 'Sadia#2026',
]);
qaAssert($employeeLogin['status'] === 200, 'AUTH-002', 'Employee login failed.', $employeeLogin['raw']);
qaAssert(! empty($employeeLogin['json']['token'] ?? null), 'AUTH-002', 'Employee token missing.', $employeeLogin['raw']);
$employeeToken = (string) $employeeLogin['json']['token'];

$invalidLogin = qaApi('POST', '/api/auth/login', null, [
    'email' => 'admin@finerp.local',
    'passkey' => 'Wrong#Pass',
]);
qaAssert($invalidLogin['status'] === 422, 'AUTH-003', 'Invalid login should fail with 422.', $invalidLogin['raw']);

Sanctum::actingAs($sadiaUser, ['employee']);
$rbac = qaApi('GET', '/api/admin/settings/general', $employeeToken);
qaAssert($rbac['status'] === 403, 'RBAC-001', 'Employee must not access admin settings.', $rbac['raw']);

Sanctum::actingAs($adminUser, ['admin']);

$deptList = qaApi('GET', '/api/admin/departments?search=QA%20Matrix', $adminToken);
qaAssert($deptList['status'] === 200, 'DEPT-001', 'Department list failed.', $deptList['raw']);
$deptRows = $deptList['json']['data'] ?? [];
qaAssert(qaFind($deptRows, fn (array $row): bool => ($row['name'] ?? '') === 'QA Matrix Department') !== null, 'DEPT-001', 'QA department missing in list.');

$employeeList = qaApi('GET', '/api/admin/employees?search=qa.matrix.employee', $adminToken);
qaAssert($employeeList['status'] === 200, 'EMP-001', 'Employee list failed.', $employeeList['raw']);
$employeeRows = $employeeList['json']['data'] ?? [];
$employeeRow = qaFind($employeeRows, fn (array $row): bool => ($row['user']['email'] ?? '') === 'qa.matrix.employee@finerp.local');
qaAssert($employeeRow !== null, 'EMP-001', 'QA employee missing from employee list.');

$employeeShow = qaApi('GET', '/api/admin/employees/'.$qaEmployee->id, $adminToken);
qaAssert($employeeShow['status'] === 200, 'EMP-002', 'Employee detail failed.', $employeeShow['raw']);
qaAssert(($employeeShow['json']['masked_bank_account'] ?? null) === '*****4321', 'EMP-002', 'Bank account masking mismatch.', $employeeShow['raw']);
qaAssert(! array_key_exists('bank_account_number', (array) $employeeShow['json']), 'EMP-002', 'Raw bank account should not be exposed.', $employeeShow['raw']);

$attendanceList = qaApi('GET', '/api/admin/attendance?employee_id='.$qaEmployee->id.'&month='.$monthDate, $adminToken);
qaAssert($attendanceList['status'] === 200, 'ATTN-001', 'Admin attendance list failed.', $attendanceList['raw']);
$attendanceRows = $attendanceList['json']['records'] ?? [];
qaAssert(count($attendanceRows) >= 3, 'ATTN-001', 'Attendance records are incomplete.');
qaAssert((int) ($attendanceList['json']['summary']['late_entries'] ?? 0) >= 1, 'ATTN-001', 'Attendance summary late count mismatch.', $attendanceList['raw']);

$attendanceShow = qaApi('GET', '/api/admin/attendance/'.$qaAttendanceLate->id, $adminToken);
qaAssert($attendanceShow['status'] === 200, 'ATTN-002', 'Attendance detail failed.', $attendanceShow['raw']);
qaAssert((int) ($attendanceShow['json']['id'] ?? 0) === (int) $qaAttendanceLate->id, 'ATTN-002', 'Attendance detail returned wrong record.', $attendanceShow['raw']);

$attendanceInvalid = qaApi('POST', '/api/admin/attendance', $adminToken, [
    'employee_id' => $qaEmployee->id,
    'date' => $monthStart->copy()->addDays(8)->toDateString(),
    'status' => 'invalid_state',
]);
qaAssert($attendanceInvalid['status'] === 422, 'ATTN-003', 'Invalid attendance status should fail.', $attendanceInvalid['raw']);

$leaveList = qaApi('GET', '/api/admin/leaves?employee_id='.$qaEmployee->id, $adminToken);
qaAssert($leaveList['status'] === 200, 'LEAVE-001', 'Leave list failed.', $leaveList['raw']);
$leaveRows = $leaveList['json']['data'] ?? [];
qaAssert(count($leaveRows) >= 2, 'LEAVE-001', 'Leave rows missing for QA employee.');

$leaveDecision = qaApi('POST', '/api/admin/leaves/'.$qaLeavePending->id.'/decision', $adminToken, [
    'status' => 'approved',
    'admin_note' => 'Approved in QA matrix',
]);
qaAssert($leaveDecision['status'] === 200, 'LEAVE-002', 'Leave decision failed.', $leaveDecision['raw']);
qaAssert(($leaveDecision['json']['leave']['status'] ?? '') === 'approved', 'LEAVE-002', 'Leave decision did not update status.', $leaveDecision['raw']);

$leaveDeleteBlocked = qaApi('DELETE', '/api/admin/leaves/'.$qaLeaveApproved->id, $adminToken);
qaAssert($leaveDeleteBlocked['status'] === 422, 'LEAVE-003', 'Approved leave delete must be blocked.', $leaveDeleteBlocked['raw']);

$loanList = qaApi('GET', '/api/admin/loans?status=pending', $adminToken);
qaAssert($loanList['status'] === 200, 'LOAN-001', 'Loan list failed.', $loanList['raw']);
$loanRows = $loanList['json']['data'] ?? [];
qaAssert(qaFind($loanRows, fn (array $row): bool => ($row['loan_reference'] ?? '') === $qaLoanPending->loan_reference) !== null, 'LOAN-001', 'Pending QA loan missing in list.');

$loanShow = qaApi('GET', '/api/admin/loans/'.$qaLoanPending->id, $adminToken);
qaAssert($loanShow['status'] === 200, 'LOAN-002', 'Loan detail failed.', $loanShow['raw']);
qaAssert(($loanShow['json']['loan']['loan_reference'] ?? '') === $qaLoanPending->loan_reference, 'LOAN-002', 'Loan detail reference mismatch.', $loanShow['raw']);

$loanDeleteBlocked = qaApi('DELETE', '/api/admin/loans/'.$qaLoanApproved->id, $adminToken);
qaAssert($loanDeleteBlocked['status'] === 422, 'LOAN-003', 'Approved loan delete must be blocked.', $loanDeleteBlocked['raw']);

$payrollMonth = qaApi('GET', '/api/admin/payroll/'.$monthDate, $adminToken);
qaAssert($payrollMonth['status'] === 200, 'PAY-001', 'Payroll month fetch failed.', $payrollMonth['raw']);
$payrollRows = $payrollMonth['json'] ?? [];
qaAssert(qaFind($payrollRows, fn (array $row): bool => (int) ($row['id'] ?? 0) === (int) $qaSalaryCurrent->id) !== null, 'PAY-001', 'QA salary row missing from payroll month list.');

$payslip = qaApi('GET', '/api/admin/payroll/'.$qaSalaryCurrent->id.'/payslip', $adminToken);
qaAssert($payslip['status'] === 200, 'PAY-002', 'Admin payslip fetch failed.', $payslip['raw']);
qaAssert(($payslip['json']['employee']['employee_code'] ?? '') === 'EMP-QA-9001', 'PAY-002', 'Payslip employee code mismatch.', $payslip['raw']);

$paidUpdateBlocked = qaApi('PUT', '/api/admin/payroll/'.$qaSalaryPaid->id, $adminToken, [
    'other_bonus' => 500,
]);
qaAssert($paidUpdateBlocked['status'] === 422, 'PAY-003', 'Paid payroll row must not be mutable.', $paidUpdateBlocked['raw']);

$messageList = qaApi('GET', '/api/admin/messages?employee_id='.$qaEmployee->id, $adminToken);
qaAssert($messageList['status'] === 200, 'MSG-001', 'Admin message list failed.', $messageList['raw']);
$messageRows = $messageList['json']['data'] ?? [];
qaAssert(qaFind($messageRows, fn (array $row): bool => ($row['subject'] ?? '') === 'QA Matrix Admin Message') !== null, 'MSG-001', 'QA admin message missing in list.');

$messageShow = qaApi('GET', '/api/admin/messages/'.$qaAdminMessage->id, $adminToken);
qaAssert($messageShow['status'] === 200, 'MSG-002', 'Admin message show failed.', $messageShow['raw']);
qaAssert((int) ($messageShow['json']['id'] ?? 0) === (int) $qaAdminMessage->id, 'MSG-002', 'Wrong message returned from show.', $messageShow['raw']);

$messageRejectInvalid = qaApi('POST', '/api/admin/messages/'.$qaAdminMessage->id.'/reject', $adminToken, []);
qaAssert($messageRejectInvalid['status'] === 422, 'MSG-003', 'Reject without reason should fail.', $messageRejectInvalid['raw']);

$settingsUpdate = qaApi('PUT', '/api/admin/settings/general', $adminToken, [
    'company_name' => 'AuxFin QA Matrix',
    'company_email' => 'qa@auxfin.local',
    'currency' => 'BDT',
    'timezone' => 'Asia/Dhaka',
    'available_cash' => 150000,
]);
qaAssert($settingsUpdate['status'] === 200, 'SET-001', 'General settings update failed.', $settingsUpdate['raw']);

$settingsGet = qaApi('GET', '/api/admin/settings/general', $adminToken);
qaAssert($settingsGet['status'] === 200, 'SET-001', 'General settings fetch failed.', $settingsGet['raw']);
qaAssert(($settingsGet['json']['general_settings']['company_name'] ?? '') === 'AuxFin QA Matrix', 'SET-001', 'General settings mismatch after update.', $settingsGet['raw']);

$holidayCreate = qaApi('POST', '/api/admin/settings/holidays', $adminToken, [
    'name' => 'QA Matrix Holiday',
    'date' => $qaHolidayDate,
    'is_optional' => false,
]);
qaAssert($holidayCreate['status'] === 201, 'SET-002', 'Holiday creation failed.', $holidayCreate['raw']);
$holidayId = (int) ($holidayCreate['json']['holiday']['id'] ?? 0);
qaAssert($holidayId > 0, 'SET-002', 'Holiday id missing after create.', $holidayCreate['raw']);

$holidayDuplicate = qaApi('POST', '/api/admin/settings/holidays', $adminToken, [
    'name' => 'QA Matrix Holiday Duplicate',
    'date' => $qaHolidayDate,
    'is_optional' => false,
]);
qaAssert($holidayDuplicate['status'] === 422, 'SET-003', 'Duplicate holiday date must fail.', $holidayDuplicate['raw']);

$holidayDelete = qaApi('DELETE', '/api/admin/settings/holidays/'.$holidayId, $adminToken);
qaAssert($holidayDelete['status'] === 200, 'SET-002', 'Holiday delete failed.', $holidayDelete['raw']);

$clients = qaApi('GET', '/api/admin/clients?search=QA%20Matrix', $adminToken);
qaAssert($clients['status'] === 200, 'FIN-001', 'Client list failed.', $clients['raw']);
$clientRows = $clients['json']['data'] ?? [];
qaAssert(qaFind($clientRows, fn (array $row): bool => ($row['name'] ?? '') === 'QA Matrix Client') !== null, 'FIN-001', 'QA client missing in client list.');

$projects = qaApi('GET', '/api/admin/projects?client_id='.$qaClient->id, $adminToken);
qaAssert($projects['status'] === 200, 'FIN-002', 'Project list failed.', $projects['raw']);
$projectRows = $projects['json']['data'] ?? [];
$projectRow = qaFind($projectRows, fn (array $row): bool => (int) ($row['id'] ?? 0) === (int) $qaProject->id);
qaAssert($projectRow !== null, 'FIN-002', 'QA project missing in project list.');
qaAssert((float) ($projectRow['accounts_receivable'] ?? 0) > 0, 'FIN-002', 'Project receivable metric should be > 0.');

$projectRevenue = qaApi('GET', '/api/admin/projects/'.$qaProject->id.'/revenue', $adminToken);
qaAssert($projectRevenue['status'] === 200, 'FIN-003', 'Project revenue endpoint failed.', $projectRevenue['raw']);
qaAssert((float) ($projectRevenue['json']['summary']['booked_revenue'] ?? 0) > 0, 'FIN-003', 'Project booked revenue should be > 0.', $projectRevenue['raw']);

$invoiceList = qaApi('GET', '/api/admin/projects/'.$qaProject->id.'/invoices', $adminToken);
qaAssert($invoiceList['status'] === 200, 'FIN-004', 'Invoice list failed.', $invoiceList['raw']);
$invoiceRows = $invoiceList['json']['data'] ?? [];
$statusSet = array_values(array_unique(array_map(static fn (array $row): string => (string) ($row['status'] ?? ''), $invoiceRows)));
qaAssert(in_array('paid', $statusSet, true) && in_array('partial', $statusSet, true) && in_array('overdue', $statusSet, true), 'FIN-004', 'Expected paid, partial, overdue statuses missing.');

$invoiceTransitionEdge = qaApi('POST', '/api/admin/projects/'.$qaProject->id.'/invoices/'.$qaInvoicePartial->id.'/status', $adminToken, [
    'status' => 'partial',
    'partial_amount' => 60000,
]);
qaAssert($invoiceTransitionEdge['status'] === 422, 'FIN-005', 'Invalid partial transition must fail.', $invoiceTransitionEdge['raw']);

$expenseList = qaApi('GET', '/api/admin/expenses?month='.$monthDate, $adminToken);
qaAssert($expenseList['status'] === 200, 'EXP-001', 'Expense list failed.', $expenseList['raw']);
$expenseRows = $expenseList['json']['data'] ?? [];
qaAssert(qaFind($expenseRows, fn (array $row): bool => ($row['description'] ?? '') === 'QA Matrix Recurring Infra') !== null, 'EXP-001', 'QA recurring expense missing in list.');

$expenseSummary = qaApi('GET', '/api/admin/expenses-summary?month='.$monthDate, $adminToken);
qaAssert($expenseSummary['status'] === 200, 'EXP-002', 'Expense summary failed.', $expenseSummary['raw']);
qaAssert((float) ($expenseSummary['json']['monthly_total'] ?? 0) >= 30000, 'EXP-002', 'Monthly expense total should include QA rows.', $expenseSummary['raw']);

$expenseEdge = qaApi('POST', '/api/admin/expenses', $adminToken, [
    'category' => 'Edge',
    'description' => 'Recurring edge expense',
    'amount' => 100,
    'expense_date' => $monthDate,
    'is_recurring' => true,
]);
qaAssert($expenseEdge['status'] === 422, 'EXP-003', 'Recurring expense without recurrence must fail.', $expenseEdge['raw']);

$liabilityList = qaApi('GET', '/api/admin/liabilities?status=active', $adminToken);
qaAssert($liabilityList['status'] === 200, 'LIA-001', 'Liability list failed.', $liabilityList['raw']);
$liabilityRows = $liabilityList['json']['data'] ?? [];
qaAssert(qaFind($liabilityRows, fn (array $row): bool => ($row['name'] ?? '') === 'QA Matrix Liability') !== null, 'LIA-001', 'QA liability missing in list.');

$dueSoon = qaApi('GET', '/api/admin/liabilities-due-soon?days=10', $adminToken);
qaAssert($dueSoon['status'] === 200, 'LIA-002', 'Liability due-soon endpoint failed.', $dueSoon['raw']);
$dueSoonRows = $dueSoon['json']['rows'] ?? [];
qaAssert(qaFind($dueSoonRows, fn (array $row): bool => ($row['name'] ?? '') === 'QA Matrix Liability') !== null, 'LIA-002', 'QA liability missing in due-soon list.');

$liabilityBefore = (float) $qaLiability->fresh()->outstanding;
$liabilityPayment = qaApi('POST', '/api/admin/liabilities/'.$qaLiability->id.'/process-payment', $adminToken, [
    'amount' => 15000,
]);
qaAssert($liabilityPayment['status'] === 200, 'LIA-003', 'Liability payment failed.', $liabilityPayment['raw']);
$liabilityAfter = (float) ($liabilityPayment['json']['liability']['outstanding'] ?? $liabilityBefore);
qaAssert($liabilityAfter < $liabilityBefore, 'LIA-003', 'Liability outstanding did not decrease.', $liabilityPayment['raw']);

$assetList = qaApi('GET', '/api/admin/assets?category=IT%20Equipment', $adminToken);
qaAssert($assetList['status'] === 200, 'AST-001', 'Asset list failed.', $assetList['raw']);
$assetRows = $assetList['json']['data'] ?? [];
qaAssert(qaFind($assetRows, fn (array $row): bool => ($row['name'] ?? '') === 'QA Matrix Laptop Fleet') !== null, 'AST-001', 'QA asset missing in list.');

$assetBefore = (float) $qaAsset->fresh()->current_book_value;
$assetDep = qaApi('POST', '/api/admin/assets/'.$qaAsset->id.'/depreciate', $adminToken);
qaAssert($assetDep['status'] === 200, 'AST-002', 'Asset depreciation failed.', $assetDep['raw']);
$assetAfter = (float) ($assetDep['json']['asset']['current_book_value'] ?? $assetBefore);
qaAssert($assetAfter < $assetBefore, 'AST-002', 'Asset book value did not decrease.', $assetDep['raw']);

$assetEdge = qaApi('PUT', '/api/admin/assets/'.$qaAsset->id, $adminToken, [
    'useful_life_months' => 0,
]);
qaAssert($assetEdge['status'] === 422, 'AST-003', 'Invalid useful life must fail validation.', $assetEdge['raw']);

$overview = qaApi('GET', '/api/admin/finance/overview?month='.$monthDate, $adminToken);
qaAssert($overview['status'] === 200, 'OVR-001', 'Finance overview failed.', $overview['raw']);
qaAssert((float) ($overview['json']['kpis']['booked_revenue'] ?? 0) > 0, 'OVR-001', 'Booked revenue KPI should be > 0.', $overview['raw']);
qaAssert(count($overview['json']['project_rows'] ?? []) > 0, 'OVR-001', 'Project rows should not be empty.', $overview['raw']);

$profitLoss = qaApi('GET', '/api/admin/reports/profit-loss?from_month='.$monthDate.'&to_month='.$monthDate, $adminToken);
qaAssert($profitLoss['status'] === 200, 'REP-001', 'Profit-loss report failed.', $profitLoss['raw']);
qaAssert(count($profitLoss['json']['rows'] ?? []) >= 1, 'REP-001', 'Profit-loss rows should not be empty.', $profitLoss['raw']);

$taxSummary = qaApi('GET', '/api/admin/reports/tax-summary?from_month='.$monthDate.'&to_month='.$monthDate, $adminToken);
qaAssert($taxSummary['status'] === 200, 'REP-002', 'Tax summary report failed.', $taxSummary['raw']);
qaAssert(array_key_exists('tax_rate_percent', (array) $taxSummary['json']), 'REP-002', 'Tax summary missing tax rate.', $taxSummary['raw']);

$arAging = qaApi('GET', '/api/admin/reports/ar-aging', $adminToken);
qaAssert($arAging['status'] === 200, 'REP-003', 'AR aging report failed.', $arAging['raw']);
qaAssert(array_key_exists('health', (array) $arAging['json']), 'REP-003', 'AR aging health payload missing.', $arAging['raw']);

$analyticsOverview = qaApi('GET', '/api/admin/analytics/overview', $adminToken);
qaAssert($analyticsOverview['status'] === 200, 'ANA-001', 'Analytics overview failed.', $analyticsOverview['raw']);
qaAssert(count($analyticsOverview['json']['series'] ?? []) > 0, 'ANA-001', 'Analytics series should not be empty.', $analyticsOverview['raw']);

$analyticsCmgr = qaApi('GET', '/api/admin/analytics/cmgr', $adminToken);
qaAssert($analyticsCmgr['status'] === 200, 'ANA-002', 'Analytics CMGR failed.', $analyticsCmgr['raw']);
qaAssert(array_key_exists('revenue_cmgr', (array) $analyticsCmgr['json']), 'ANA-002', 'CMGR payload missing revenue key.', $analyticsCmgr['raw']);

$analyticsForecast = qaApi('GET', '/api/admin/analytics/forecast', $adminToken);
qaAssert($analyticsForecast['status'] === 200, 'ANA-003', 'Analytics forecast failed.', $analyticsForecast['raw']);
qaAssert(array_key_exists('p50', (array) $analyticsForecast['json']), 'ANA-003', 'Forecast payload missing p50.', $analyticsForecast['raw']);

$analyticsAnomalies = qaApi('GET', '/api/admin/analytics/anomalies', $adminToken);
qaAssert($analyticsAnomalies['status'] === 200, 'ANA-004', 'Analytics anomalies failed.', $analyticsAnomalies['raw']);
qaAssert(is_array($analyticsAnomalies['json']), 'ANA-004', 'Anomalies payload should be an array.', $analyticsAnomalies['raw']);

$analyticsBurn = qaApi('GET', '/api/admin/analytics/burn-rate?available_cash=100000', $adminToken);
qaAssert($analyticsBurn['status'] === 200, 'ANA-005', 'Analytics burn-rate failed.', $analyticsBurn['raw']);
qaAssert(array_key_exists('cash_runway_months', (array) $analyticsBurn['json']), 'ANA-005', 'Burn-rate payload missing runway.', $analyticsBurn['raw']);

$analyticsArHealth = qaApi('GET', '/api/admin/analytics/ar-health', $adminToken);
qaAssert($analyticsArHealth['status'] === 200, 'ANA-006', 'Analytics AR health failed.', $analyticsArHealth['raw']);
qaAssert(array_key_exists('score', (array) $analyticsArHealth['json']), 'ANA-006', 'AR health payload missing score.', $analyticsArHealth['raw']);

$analyticsGrowth = qaApi('GET', '/api/admin/analytics/growth', $adminToken);
qaAssert($analyticsGrowth['status'] === 200, 'ANA-007', 'Analytics growth failed.', $analyticsGrowth['raw']);
qaAssert(array_key_exists('payroll_efficiency', (array) $analyticsGrowth['json']), 'ANA-007', 'Growth payload missing payroll efficiency.', $analyticsGrowth['raw']);

Sanctum::actingAs($sadiaUser, ['employee']);
$employeeDashboard = qaApi('GET', '/api/employee/dashboard', $employeeToken);
qaAssert($employeeDashboard['status'] === 200, 'EDB-001', 'Employee dashboard failed.', $employeeDashboard['raw']);
qaAssert(array_key_exists('attendance_summary', (array) $employeeDashboard['json']), 'EDB-001', 'Dashboard missing attendance summary.', $employeeDashboard['raw']);

$employeeSalaryList = qaApi('GET', '/api/employee/salary', $employeeToken);
qaAssert($employeeSalaryList['status'] === 200, 'ESM-001', 'Employee salary list failed.', $employeeSalaryList['raw']);
$employeeSalaryRows = $employeeSalaryList['json']['data'] ?? [];
qaAssert(count($employeeSalaryRows) > 0, 'ESM-001', 'Employee salary list is empty.');
$salaryMonthValue = (string) ($employeeSalaryRows[0]['month'] ?? $monthDate);

$employeePayslip = qaApi('GET', '/api/employee/salary/'.$salaryMonthValue.'/payslip', $employeeToken);
qaAssert($employeePayslip['status'] === 200, 'ESM-002', 'Employee payslip failed.', $employeePayslip['raw']);
qaAssert(array_key_exists('net_payable', (array) $employeePayslip['json']), 'ESM-002', 'Employee payslip missing net payable.', $employeePayslip['raw']);

$employeePayslipPdf = qaApi('GET', '/api/employee/salary/'.$salaryMonthValue.'/payslip/pdf', $employeeToken);
qaAssert($employeePayslipPdf['status'] === 200, 'ESM-003', 'Employee payslip pdf payload failed.', $employeePayslipPdf['raw']);
qaAssert(str_ends_with((string) ($employeePayslipPdf['json']['filename'] ?? ''), '.pdf'), 'ESM-003', 'Payslip PDF filename missing .pdf suffix.', $employeePayslipPdf['raw']);

$employeeLoans = qaApi('GET', '/api/employee/loans', $employeeToken);
qaAssert($employeeLoans['status'] === 200, 'ELN-001', 'Employee loan list failed.', $employeeLoans['raw']);
qaAssert(count($employeeLoans['json'] ?? []) > 0, 'ELN-001', 'Employee loan list is empty.');

$employeeLoanPolicy = qaApi('GET', '/api/employee/loans/policy', $employeeToken);
qaAssert($employeeLoanPolicy['status'] === 200, 'ELN-002', 'Employee loan policy failed.', $employeeLoanPolicy['raw']);
qaAssert((float) ($employeeLoanPolicy['json']['max_amount_for_employee'] ?? 0) > 0, 'ELN-002', 'Loan policy max amount should be > 0.', $employeeLoanPolicy['raw']);

$loanApplyEdge = qaApi('POST', '/api/employee/loans/apply', $employeeToken, [
    'amount_requested' => 999999,
    'reason' => 'Exceeds policy edge case',
    'preferred_repayment_months' => 12,
]);
qaAssert($loanApplyEdge['status'] === 422, 'ELN-003', 'Loan apply over policy should fail.', $loanApplyEdge['raw']);

$employeeLeaves = qaApi('GET', '/api/employee/leaves', $employeeToken);
qaAssert($employeeLeaves['status'] === 200, 'ELV-001', 'Employee leave list failed.', $employeeLeaves['raw']);
qaAssert(count($employeeLeaves['json'] ?? []) > 0, 'ELV-001', 'Employee leave list is empty.');

$leaveApplyEdge = qaApi('POST', '/api/employee/leaves/apply', $employeeToken, [
    'leave_type' => 'casual',
    'from_date' => $monthStart->copy()->addDays(25)->toDateString(),
    'to_date' => $monthStart->copy()->addDays(24)->toDateString(),
    'reason' => 'Invalid date range edge case',
]);
qaAssert($leaveApplyEdge['status'] === 422, 'ELV-002', 'Leave apply with invalid range must fail.', $leaveApplyEdge['raw']);

$employeeAttendance = qaApi('GET', '/api/employee/attendance?month='.$monthDate, $employeeToken);
qaAssert($employeeAttendance['status'] === 200, 'EAT-001', 'Employee attendance failed.', $employeeAttendance['raw']);
qaAssert(count($employeeAttendance['json']['records'] ?? []) > 0, 'EAT-001', 'Employee attendance records should not be empty.', $employeeAttendance['raw']);

$employeeMessages = qaApi('GET', '/api/employee/messages', $employeeToken);
qaAssert($employeeMessages['status'] === 200, 'EMS-001', 'Employee inbox failed.', $employeeMessages['raw']);
$employeeMessageRows = $employeeMessages['json']['data'] ?? [];
$employeeMessageRow = qaFind($employeeMessageRows, fn (array $row): bool => ($row['subject'] ?? '') === 'QA Matrix Employee Inbox');
qaAssert($employeeMessageRow !== null, 'EMS-001', 'Employee inbox missing QA message.');

$messageDetail = qaApi('GET', '/api/employee/messages/'.$qaEmployeeMessage->id, $employeeToken);
qaAssert($messageDetail['status'] === 200, 'EMS-002', 'Employee message detail failed.', $messageDetail['raw']);

$markAllRead = qaApi('POST', '/api/employee/messages/mark-all-read', $employeeToken, []);
qaAssert($markAllRead['status'] === 200, 'EMS-003', 'Employee mark-all-read failed.', $markAllRead['raw']);

$messageCreateEdge = qaApi('POST', '/api/employee/messages', $employeeToken, [
    'type' => 'general_hr',
    'subject' => 'Too short body edge',
    'body' => 'bad',
]);
qaAssert($messageCreateEdge['status'] === 422, 'EMS-004', 'Employee message body length validation should fail.', $messageCreateEdge['raw']);

qaLog('All matrix checks passed.');

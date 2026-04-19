<?php

use App\Models\Employee;
use App\Models\EmployeeMessage;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createAdminUser(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'passkey' => 'Admin#1234',
    ]);
}

function createEmployeeProfile(string $email = 'crud.employee@test.local'): Employee
{
    $user = User::factory()->create([
        'email' => $email,
        'role' => 'employee',
        'passkey' => 'Emp#1234',
    ]);

    return Employee::query()->create([
        'user_id' => $user->id,
        'employee_code' => 'EMP-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
        'designation' => 'Associate',
        'date_of_joining' => now()->subMonths(10)->toDateString(),
        'basic_salary' => 30000,
        'house_rent' => 10000,
        'conveyance' => 3000,
        'medical_allowance' => 2000,
        'pf_rate' => 10,
        'tds_rate' => 5,
        'professional_tax' => 300,
    ]);
}

test('admin can create update and delete a loan request', function () {
    $admin = createAdminUser();
    $employee = createEmployeeProfile('loan-crud@test.local');

    Sanctum::actingAs($admin, ['admin']);

    $create = $this->postJson('/api/admin/loans', [
        'employee_id' => $employee->id,
        'amount_requested' => 5000,
        'reason' => 'Laptop replacement due to damage',
        'preferred_repayment_months' => 10,
    ])->assertCreated();

    $loanId = $create->json('loan.id');

    $this->putJson("/api/admin/loans/{$loanId}", [
        'amount_requested' => 4500,
        'reason' => 'Updated reason for request',
        'status' => 'rejected',
        'admin_note' => 'Need revised documents.',
    ])->assertOk()->assertJsonPath('loan.status', 'rejected');

    $this->deleteJson("/api/admin/loans/{$loanId}")
        ->assertOk();

    expect(Loan::query()->find($loanId))->toBeNull();
});

test('admin can create update and delete leave records', function () {
    $admin = createAdminUser();
    $employee = createEmployeeProfile('leave-crud@test.local');

    Sanctum::actingAs($admin, ['admin']);

    $create = $this->postJson('/api/admin/leaves', [
        'employee_id' => $employee->id,
        'leave_type' => 'casual',
        'from_date' => now()->toDateString(),
        'to_date' => now()->addDay()->toDateString(),
        'reason' => 'Family commitment',
    ])->assertCreated();

    $leaveId = $create->json('leave.id');

    $this->putJson("/api/admin/leaves/{$leaveId}", [
        'status' => 'rejected',
        'admin_note' => 'Overlapping with critical deployment week.',
    ])->assertOk()->assertJsonPath('leave.status', 'rejected');

    $this->deleteJson("/api/admin/leaves/{$leaveId}")
        ->assertOk();

    expect(Leave::query()->find($leaveId))->toBeNull();
});

test('admin can view and delete attendance records', function () {
    $admin = createAdminUser();
    $employee = createEmployeeProfile('attendance-crud@test.local');

    Sanctum::actingAs($admin, ['admin']);

    $create = $this->postJson('/api/admin/attendance', [
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'check_in' => '09:00',
        'check_out' => '18:00',
    ])->assertOk();

    $recordId = $create->json('record.id');

    $this->getJson("/api/admin/attendance/{$recordId}")
        ->assertOk()
        ->assertJsonPath('id', $recordId);

    $this->deleteJson("/api/admin/attendance/{$recordId}")
        ->assertOk();
});

test('admin can create and delete message records', function () {
    $admin = createAdminUser();
    $employee = createEmployeeProfile('message-crud@test.local');

    Sanctum::actingAs($admin, ['admin']);

    $create = $this->postJson('/api/admin/messages', [
        'employee_id' => $employee->id,
        'type' => 'general_hr',
        'subject' => 'Monthly policy reminder',
        'body' => 'Please submit your updated profile documents.',
        'priority' => 'normal',
    ])->assertCreated();

    $messageId = $create->json('record.id');

    $this->deleteJson("/api/admin/messages/{$messageId}")
        ->assertOk();

    expect(EmployeeMessage::query()->find($messageId))->toBeNull();
});

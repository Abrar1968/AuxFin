<?php

use App\Models\Employee;
use App\Models\EmployeeMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createEmployeeWithUser(string $email, string $role = 'employee'): array
{
    $user = User::factory()->create([
        'email' => $email,
        'role' => $role,
        'passkey' => 'Pass#9090',
        'is_active' => true,
    ]);

    $employee = Employee::query()->create([
        'user_id' => $user->id,
        'employee_code' => 'EMP-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
        'designation' => 'Officer',
        'date_of_joining' => now()->subYear()->toDateString(),
        'basic_salary' => 32000,
        'house_rent' => 9000,
        'conveyance' => 2500,
        'medical_allowance' => 1500,
        'pf_rate' => 10,
        'tds_rate' => 5,
        'professional_tax' => 200,
    ]);

    return [$user, $employee];
}

test('auth login updates login metadata and issues employee ability token', function () {
    [$user] = createEmployeeWithUser('authmeta@test.local');

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'passkey' => 'Pass#9090',
    ])->assertOk()->assertJsonPath('user.role', 'employee');

    $user->refresh();
    $token = PersonalAccessToken::query()->latest('id')->first();

    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_ip)->not->toBeNull()
        ->and($token)->not->toBeNull()
        ->and($token->abilities)->toContain('employee');
});

test('inactive account is blocked from login', function () {
    $user = User::factory()->create([
        'email' => 'inactive@test.local',
        'role' => 'employee',
        'is_active' => false,
        'passkey' => 'Inactive#1',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'passkey' => 'Inactive#1',
    ])->assertStatus(403);
});

test('employee cannot access admin endpoints and admin can access them', function () {
    [$employeeUser] = createEmployeeWithUser('emp-admin-guard@test.local');
    [$adminUser] = createEmployeeWithUser('admin-access@test.local', 'admin');

    Sanctum::actingAs($employeeUser, ['employee']);
    $this->getJson('/api/admin/settings/general')->assertForbidden();

    Sanctum::actingAs($adminUser, ['admin']);
    $this->getJson('/api/admin/settings/general')->assertOk();
});

test('message reply endpoint works with realtime event dispatch pipeline', function () {
    [$adminUser] = createEmployeeWithUser('admin-msg@test.local', 'admin');
    [, $employee] = createEmployeeWithUser('employee-msg@test.local', 'employee');

    $message = EmployeeMessage::query()->create([
        'employee_id' => $employee->id,
        'type' => 'salary_query',
        'subject' => 'Clarification needed',
        'body' => 'Please explain deduction components.',
        'status' => 'open',
        'priority' => 'normal',
        'action_taken' => 'none',
    ]);

    Sanctum::actingAs($adminUser, ['admin']);

    $this->postJson("/api/admin/messages/{$message->id}/reply", [
        'admin_reply' => 'Breakdown sent and confirmed.',
        'action_taken' => 'none',
        'status' => 'resolved',
    ])->assertOk();

    expect($message->fresh()->status)->toBe('resolved');
});

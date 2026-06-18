<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    /**
     * @return array{employee: Employee, passkey: string}
     */
    public function create(array $payload, int $createdBy): array
    {
        $plainPasskey = $this->generatePasskey();

        $employee = DB::transaction(function () use ($payload, $plainPasskey, $createdBy): Employee {
            $user = User::query()->create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'passkey' => $plainPasskey,
                'passkey_plain' => $plainPasskey,
                'role' => $payload['role'] ?? 'employee',
                'created_by' => $createdBy,
            ]);

            $employee = Employee::query()->create([
                'user_id' => $user->id,
                'employee_code' => $this->generateEmployeeCode(),
                'department_id' => $payload['department_id'] ?? null,
                'designation' => $payload['designation'],
                'date_of_joining' => $payload['date_of_joining'],
                'bank_account_number' => $payload['bank_account_number'] ?? null,
                'bank_name' => $payload['bank_name'] ?? null,
                'basic_salary' => $payload['basic_salary'],
                'house_rent' => $payload['house_rent'] ?? 0,
                'conveyance' => $payload['conveyance'] ?? 0,
                'medical_allowance' => $payload['medical_allowance'] ?? 0,
                'pf_rate' => $payload['pf_rate'] ?? 0,
                'tds_rate' => $payload['tds_rate'] ?? 0,
                'professional_tax' => $payload['professional_tax'] ?? 0,
                'late_threshold_days' => $payload['late_threshold_days'] ?? 3,
                'late_penalty_type' => $payload['late_penalty_type'] ?? 'half_day',
                'working_days_per_week' => $payload['working_days_per_week'] ?? 5,
                'weekly_off_days' => $payload['weekly_off_days'] ?? [],
            ]);

            $user->update(['passkey_plain' => null]);

            return $employee;
        });

        return [
            'employee' => $employee,
            'passkey' => $plainPasskey,
        ];
    }

    public function update(Employee $employee, array $payload): Employee
    {
        DB::transaction(function () use ($employee, $payload): void {
            $employee->user->update(array_filter([
                'name' => $payload['name'] ?? null,
                'email' => $payload['email'] ?? null,
                'is_active' => $payload['is_active'] ?? null,
            ], static fn ($value) => $value !== null));

            $employee->update(collect($payload)->except(['name', 'email', 'is_active'])->toArray());
        });

        return $employee->fresh(['user', 'department']);
    }

    public function archive(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $employee->delete();
            $employee->user?->update(['is_active' => false]);
        });
    }

    public function resetPasskey(Employee $employee): string
    {
        $plainPasskey = $this->generatePasskey();

        $employee->user->update([
            'passkey' => $plainPasskey,
            'passkey_plain' => null,
        ]);

        return $plainPasskey;
    }

    private function generatePasskey(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
        $max = strlen($alphabet) - 1;
        $passkey = '';

        for ($i = 0; $i < $length; $i++) {
            $passkey .= $alphabet[random_int(0, $max)];
        }

        return $passkey;
    }

    private function generateEmployeeCode(): string
    {
        $next = Employee::query()->withTrashed()->count() + 1;

        return sprintf('EMP-%04d', $next);
    }
}

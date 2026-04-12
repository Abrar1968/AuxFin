<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->with(['user', 'department'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = (string) $request->query('search');
                $q->where(function ($sub) use ($search): void {
                    $sub->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($employees);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email'],
            'role' => ['nullable', 'in:employee,admin'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation' => ['required', 'string', 'max:150'],
            'date_of_joining' => ['required', 'date'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'house_rent' => ['nullable', 'numeric', 'min:0'],
            'conveyance' => ['nullable', 'numeric', 'min:0'],
            'medical_allowance' => ['nullable', 'numeric', 'min:0'],
            'pf_rate' => ['nullable', 'numeric', 'min:0'],
            'tds_rate' => ['nullable', 'numeric', 'min:0'],
            'professional_tax' => ['nullable', 'numeric', 'min:0'],
            'late_threshold_days' => ['nullable', 'integer', 'min:1'],
            'late_penalty_type' => ['nullable', 'in:half_day,full_day'],
            'working_days_per_week' => ['nullable', 'integer', 'between:1,7'],
            'weekly_off_days' => ['nullable', 'array'],
        ]);

        $plainPasskey = $this->generatePasskey();

        $employee = DB::transaction(function () use ($payload, $plainPasskey, $request) {
            $user = User::query()->create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'passkey' => $plainPasskey,
                'passkey_plain' => $plainPasskey,
                'role' => $payload['role'] ?? 'employee',
                'created_by' => $request->user()->id,
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

        return response()->json([
            'message' => 'Employee created successfully.',
            'passkey' => $plainPasskey,
            'employee' => $employee->load(['user', 'department']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $employee = Employee::query()
            ->with([
                'user',
                'department',
                'salaryMonths' => fn ($q) => $q->latest('month')->limit(24),
                'loans',
                'leaveRequests',
            ])
            ->findOrFail($id);

        return response()->json($employee);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $employee = Employee::query()->with('user')->findOrFail($id);

        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:200', 'unique:users,email,'.$employee->user_id],
            'is_active' => ['sometimes', 'boolean'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation' => ['sometimes', 'string', 'max:150'],
            'date_of_joining' => ['sometimes', 'date'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
            'house_rent' => ['sometimes', 'numeric', 'min:0'],
            'conveyance' => ['sometimes', 'numeric', 'min:0'],
            'medical_allowance' => ['sometimes', 'numeric', 'min:0'],
            'pf_rate' => ['sometimes', 'numeric', 'min:0'],
            'tds_rate' => ['sometimes', 'numeric', 'min:0'],
            'professional_tax' => ['sometimes', 'numeric', 'min:0'],
            'working_days_per_week' => ['sometimes', 'integer', 'between:1,7'],
            'weekly_off_days' => ['sometimes', 'array'],
        ]);

        DB::transaction(function () use ($employee, $payload): void {
            $employee->user->update(array_filter([
                'name' => $payload['name'] ?? null,
                'email' => $payload['email'] ?? null,
                'is_active' => $payload['is_active'] ?? null,
            ], static fn ($value) => $value !== null));

            $employee->update(collect($payload)->except(['name', 'email', 'is_active'])->toArray());
        });

        return response()->json([
            'message' => 'Employee updated successfully.',
            'employee' => $employee->fresh()->load(['user', 'department']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $employee = Employee::query()->with('user')->findOrFail($id);

        DB::transaction(function () use ($employee): void {
            $employee->update(['deleted_at' => now()]);
            $employee->user?->update(['is_active' => false]);
        });

        return response()->json(['message' => 'Employee archived successfully.']);
    }

    public function resetPasskey(int $id): JsonResponse
    {
        $employee = Employee::query()->with('user')->findOrFail($id);
        $plainPasskey = $this->generatePasskey();

        $employee->user->update([
            'passkey' => $plainPasskey,
            'passkey_plain' => null,
        ]);

        return response()->json([
            'message' => 'Passkey reset successfully.',
            'passkey' => $plainPasskey,
        ]);
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
        $next = Employee::query()->count() + 1;

        return sprintf('EMP-%04d', $next);
    }
}

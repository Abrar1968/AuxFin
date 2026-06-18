<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;
use App\Models\Employee;

class EmployeeRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
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
            ],
            'update' => [
                'name' => ['sometimes', 'string', 'max:150'],
                'email' => ['sometimes', 'email', 'max:200', 'unique:users,email,'.$this->userIdForUpdate()],
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
            ],
            default => [],
        };
    }

    private function userIdForUpdate(): int
    {
        $employeeId = $this->routeParameterInt(['employee', 'id']);

        return (int) (Employee::query()->find($employeeId)?->user_id ?? 0);
    }
}

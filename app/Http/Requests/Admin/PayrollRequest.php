<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class PayrollRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => [
                'page' => ['nullable', 'integer', 'min:1'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            ],
            'process' => [
                'employee_id' => ['required', 'exists:employees,id'],
                'month' => ['required', 'date'],
                'performance_bonus' => ['nullable', 'numeric', 'min:0'],
                'festival_bonus' => ['nullable', 'numeric', 'min:0'],
                'overtime_pay' => ['nullable', 'numeric', 'min:0'],
                'other_bonus' => ['nullable', 'numeric', 'min:0'],
            ],
            'bulkProcess' => [
                'month' => ['required', 'date'],
            ],
            'update' => [
                'performance_bonus' => ['sometimes', 'numeric', 'min:0'],
                'festival_bonus' => ['sometimes', 'numeric', 'min:0'],
                'overtime_pay' => ['sometimes', 'numeric', 'min:0'],
                'other_bonus' => ['sometimes', 'numeric', 'min:0'],
                'tds_deduction' => ['sometimes', 'numeric', 'min:0'],
                'pf_deduction' => ['sometimes', 'numeric', 'min:0'],
                'professional_tax' => ['sometimes', 'numeric', 'min:0'],
                'unpaid_leave_deduction' => ['sometimes', 'numeric', 'min:0'],
                'late_penalty_deduction' => ['sometimes', 'numeric', 'min:0'],
                'loan_emi_deduction' => ['sometimes', 'numeric', 'min:0'],
            ],
            default => [],
        };
    }
}

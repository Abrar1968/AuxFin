<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class LoanRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
                'employee_id' => ['required', 'exists:employees,id'],
                'amount_requested' => ['required', 'numeric', 'min:1'],
                'reason' => ['required', 'string', 'min:3'],
                'preferred_repayment_months' => ['nullable', 'integer', 'between:1,60'],
            ],
            'update' => [
                'amount_requested' => ['sometimes', 'numeric', 'min:1'],
                'reason' => ['sometimes', 'string', 'min:3'],
                'status' => ['sometimes', 'in:pending,rejected'],
                'admin_note' => ['nullable', 'string'],
            ],
            'approve' => [
                'amount_approved' => ['required', 'numeric', 'min:1'],
                'repayment_months' => ['required', 'integer', 'between:1,60'],
                'start_month' => ['required', 'date'],
                'admin_note' => ['nullable', 'string'],
            ],
            'reject' => [
                'admin_note' => ['required', 'string', 'min:3'],
            ],
            default => [],
        };
    }
}

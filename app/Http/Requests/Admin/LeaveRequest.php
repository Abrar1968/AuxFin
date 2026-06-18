<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class LeaveRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
                'employee_id' => ['required', 'exists:employees,id'],
                'leave_type' => ['required', 'in:casual,sick,earned,unpaid'],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
                'reason' => ['required', 'string', 'min:3'],
                'status' => ['nullable', 'in:pending,approved,rejected'],
                'admin_note' => ['nullable', 'string', 'required_if:status,rejected'],
            ],
            'update' => [
                'leave_type' => ['sometimes', 'in:casual,sick,earned,unpaid'],
                'from_date' => ['sometimes', 'date'],
                'to_date' => ['sometimes', 'date'],
                'reason' => ['sometimes', 'string', 'min:3'],
                'status' => ['sometimes', 'in:pending,approved,rejected'],
                'admin_note' => ['nullable', 'string'],
            ],
            'decision' => [
                'status' => ['required', 'in:approved,rejected'],
                'admin_note' => ['nullable', 'string', 'min:3', 'required_if:status,rejected'],
            ],
            default => [],
        };
    }
}

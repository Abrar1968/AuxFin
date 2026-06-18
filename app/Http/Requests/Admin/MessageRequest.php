<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class MessageRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
                'employee_id' => ['required', 'exists:employees,id'],
                'type' => ['required', 'in:late_appeal,deduction_dispute,leave_clarification,salary_query,loan_query,general_hr'],
                'subject' => ['required', 'string', 'max:300'],
                'body' => ['required', 'string', 'min:5'],
                'reference_date' => ['nullable', 'date'],
                'reference_month' => ['nullable', 'date'],
                'priority' => ['nullable', 'in:normal,high'],
                'status' => ['nullable', 'in:open,under_review,resolved,rejected'],
                'admin_reply' => ['nullable', 'string', 'min:3'],
                'action_taken' => ['nullable', 'in:none,deduction_reversed,mark_excused,salary_adjusted,noted'],
            ],
            'reply' => [
                'admin_reply' => ['required', 'string', 'min:3'],
                'action_taken' => ['nullable', 'in:none,deduction_reversed,mark_excused,salary_adjusted,noted'],
                'status' => ['nullable', 'in:under_review,resolved,rejected'],
            ],
            'reject' => [
                'reason' => ['required', 'string', 'min:3'],
            ],
            default => [],
        };
    }
}

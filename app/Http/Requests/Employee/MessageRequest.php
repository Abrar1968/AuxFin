<?php

namespace App\Http\Requests\Employee;

use App\Http\Requests\AuthorizedRequest;

class MessageRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:late_appeal,deduction_dispute,leave_clarification,salary_query,loan_query,general_hr'],
            'subject' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string', 'min:5'],
            'reference_date' => ['nullable', 'date'],
            'reference_month' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:normal,high'],
        ];
    }
}

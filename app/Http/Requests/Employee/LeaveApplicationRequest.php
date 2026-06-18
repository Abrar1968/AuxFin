<?php

namespace App\Http\Requests\Employee;

use App\Http\Requests\AuthorizedRequest;

class LeaveApplicationRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return [
            'leave_type' => ['required', 'in:casual,sick,earned,unpaid'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'min:3'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class AttendanceRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => [
                'employee_id' => ['required', 'exists:employees,id'],
                'month' => ['nullable', 'date'],
            ],
            'upsert' => [
                'employee_id' => ['required', 'exists:employees,id'],
                'date' => ['required', 'date'],
                'status' => ['required', 'in:present,absent,late,weekly_off,holiday'],
                'check_in' => ['nullable', 'date_format:H:i'],
                'check_out' => ['nullable', 'date_format:H:i'],
                'is_late' => ['nullable', 'boolean'],
                'late_minutes' => ['nullable', 'integer', 'min:0'],
            ],
            default => [],
        };
    }
}

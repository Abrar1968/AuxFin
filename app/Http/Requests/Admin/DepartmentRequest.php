<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        $departmentId = $this->routeParameterInt(['department', 'id']);

        return match ($this->actionMethod()) {
            'store' => [
                'name' => ['required', 'string', 'max:150', 'unique:departments,name'],
                'head_id' => ['nullable', 'exists:employees,id'],
            ],
            'update' => [
                'name' => [
                    'sometimes',
                    'string',
                    'max:150',
                    Rule::unique('departments', 'name')->ignore($departmentId),
                ],
                'head_id' => ['nullable', 'exists:employees,id'],
            ],
            default => [],
        };
    }
}

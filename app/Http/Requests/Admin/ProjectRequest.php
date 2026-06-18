<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class ProjectRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
                'client_id' => ['required', 'exists:clients,id'],
                'name' => ['required', 'string', 'max:200'],
                'description' => ['nullable', 'string'],
                'contract_amount' => ['required', 'numeric', 'min:0'],
                'status' => ['nullable', 'in:active,completed,on_hold,cancelled'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            ],
            'update' => [
                'client_id' => ['sometimes', 'exists:clients,id'],
                'name' => ['sometimes', 'string', 'max:200'],
                'description' => ['nullable', 'string'],
                'contract_amount' => ['sometimes', 'numeric', 'min:0'],
                'status' => ['sometimes', 'in:active,completed,on_hold,cancelled'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            ],
            default => [],
        };
    }
}

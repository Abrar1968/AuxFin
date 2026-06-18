<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class LiabilityRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
                'name' => ['required', 'string', 'max:200'],
                'principal_amount' => ['required', 'numeric', 'min:0.01'],
                'outstanding' => ['nullable', 'numeric', 'min:0'],
                'interest_rate' => ['nullable', 'numeric', 'min:0'],
                'monthly_payment' => ['required', 'numeric', 'min:0.01'],
                'start_date' => ['required', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'next_due_date' => ['nullable', 'date'],
                'status' => ['nullable', 'in:active,completed,defaulted'],
            ],
            'update' => [
                'name' => ['sometimes', 'string', 'max:200'],
                'principal_amount' => ['sometimes', 'numeric', 'min:0.01'],
                'outstanding' => ['sometimes', 'numeric', 'min:0'],
                'interest_rate' => ['sometimes', 'numeric', 'min:0'],
                'monthly_payment' => ['sometimes', 'numeric', 'min:0.01'],
                'start_date' => ['sometimes', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'next_due_date' => ['nullable', 'date'],
                'status' => ['sometimes', 'in:active,completed,defaulted'],
            ],
            'processPayment' => [
                'amount' => ['nullable', 'numeric', 'min:0.01'],
            ],
            default => [],
        };
    }
}

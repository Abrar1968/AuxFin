<?php

namespace App\Http\Requests\Employee;

use App\Http\Requests\AuthorizedRequest;

class LoanApplicationRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return [
            'amount_requested' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'min:3'],
            'preferred_repayment_months' => ['nullable', 'integer', 'between:1,12'],
        ];
    }
}

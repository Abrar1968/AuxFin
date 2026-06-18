<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class ProjectPaymentRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return [
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

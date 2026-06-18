<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class InvoiceRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        $invoiceId = $this->routeParameterInt(['id', 'invoice']);

        return match ($this->actionMethod()) {
            'store' => [
                'invoice_number' => ['nullable', 'string', 'max:30', 'unique:invoices,invoice_number'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'invoice_date' => ['nullable', 'date'],
                'due_date' => ['required', 'date'],
                'status' => ['nullable', 'in:draft,sent,partial,paid,overdue'],
                'partial_amount' => ['nullable', 'numeric', 'min:0'],
                'paid_at' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
            ],
            'update' => [
                'invoice_number' => ['sometimes', 'string', 'max:30', 'unique:invoices,invoice_number,'.$invoiceId],
                'amount' => ['sometimes', 'numeric', 'min:0.01'],
                'invoice_date' => ['sometimes', 'date'],
                'due_date' => ['sometimes', 'date'],
                'notes' => ['nullable', 'string'],
            ],
            'transition' => [
                'status' => ['required', 'in:draft,sent,partial,paid,overdue'],
                'partial_amount' => ['nullable', 'numeric', 'gt:0'],
                'paid_at' => ['nullable', 'date'],
            ],
            default => [],
        };
    }
}

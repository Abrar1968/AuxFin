<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class ExpenseRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
                'category' => ['required', 'string', 'max:100'],
                'description' => ['required', 'string'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'accounting_mode' => ['nullable', 'in:cash,payable,prepaid'],
                'expense_date' => ['required', 'date'],
                'payable_due_date' => ['nullable', 'date'],
                'prepaid_start_date' => ['nullable', 'date'],
                'prepaid_months' => ['nullable', 'integer', 'min:1'],
                'is_recurring' => ['nullable', 'boolean'],
                'recurrence' => ['nullable', 'in:monthly,quarterly,yearly'],
                'next_due_date' => ['nullable', 'date'],
            ],
            'update' => [
                'category' => ['sometimes', 'string', 'max:100'],
                'description' => ['sometimes', 'string'],
                'amount' => ['sometimes', 'numeric', 'min:0.01'],
                'accounting_mode' => ['sometimes', 'in:cash,payable,prepaid'],
                'expense_date' => ['sometimes', 'date'],
                'payable_due_date' => ['nullable', 'date'],
                'prepaid_start_date' => ['nullable', 'date'],
                'prepaid_months' => ['nullable', 'integer', 'min:1'],
                'is_recurring' => ['nullable', 'boolean'],
                'recurrence' => ['nullable', 'in:monthly,quarterly,yearly'],
                'next_due_date' => ['nullable', 'date'],
            ],
            'recordPayment' => [
                'amount' => ['required', 'numeric', 'min:0.01'],
                'payment_date' => ['required', 'date'],
                'payment_method' => ['nullable', 'string', 'max:40'],
                'reference_number' => ['nullable', 'string', 'max:80'],
                'notes' => ['nullable', 'string'],
            ],
            default => [],
        };
    }
}

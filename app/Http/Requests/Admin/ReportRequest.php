<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class ReportRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'profitLoss', 'taxSummary', 'cashFlow' => [
                'from_month' => ['nullable', 'date'],
                'to_month' => ['nullable', 'date'],
                'timeframe' => ['nullable', 'in:day,week,month,year'],
                'anchor_date' => ['nullable', 'date'],
            ],
            'arAging', 'trialBalance', 'balanceSheet' => [
                'as_of' => ['nullable', 'date'],
                'timeframe' => ['nullable', 'in:day,week,month,year'],
                'anchor_date' => ['nullable', 'date'],
            ],
            'generalLedger', 'paymentLedger' => [
                'from_date' => ['nullable', 'date'],
                'to_date' => ['nullable', 'date'],
                'project_id' => ['nullable', 'integer', 'exists:projects,id'],
                'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
                'page' => ['nullable', 'integer', 'min:1'],
                'timeframe' => ['nullable', 'in:day,week,month,year'],
                'anchor_date' => ['nullable', 'date'],
            ],
            default => [],
        };
    }
}

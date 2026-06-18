<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class OwnerEquityRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'storeOwner' => [
                'name' => ['required', 'string', 'max:120'],
                'ownership_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'initial_investment' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'updateOwner' => [
                'name' => ['sometimes', 'string', 'max:120'],
                'ownership_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'initial_investment' => ['sometimes', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'store' => [
                'business_owner_id' => ['nullable', 'integer', 'exists:business_owners,id'],
                'entry_date' => ['required', 'date'],
                'entry_type' => ['required', 'in:capital_contribution,drawing'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'notes' => ['nullable', 'string'],
            ],
            'update' => [
                'business_owner_id' => ['nullable', 'integer', 'exists:business_owners,id'],
                'entry_date' => ['sometimes', 'date'],
                'entry_type' => ['sometimes', 'in:capital_contribution,drawing'],
                'amount' => ['sometimes', 'numeric', 'min:0.01'],
                'notes' => ['nullable', 'string'],
            ],
            default => [],
        };
    }
}

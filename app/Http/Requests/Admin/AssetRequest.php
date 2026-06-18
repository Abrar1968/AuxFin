<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class AssetRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
                'name' => ['required', 'string', 'max:200'],
                'category' => ['required', 'string', 'max:100'],
                'purchase_date' => ['required', 'date'],
                'purchase_cost' => ['required', 'numeric', 'min:0.01'],
                'useful_life_months' => ['required', 'integer', 'min:1'],
                'current_book_value' => ['nullable', 'numeric', 'min:0'],
                'status' => ['nullable', 'in:active,disposed,fully_depreciated'],
            ],
            'update' => [
                'name' => ['sometimes', 'string', 'max:200'],
                'category' => ['sometimes', 'string', 'max:100'],
                'purchase_date' => ['sometimes', 'date'],
                'purchase_cost' => ['sometimes', 'numeric', 'min:0.01'],
                'useful_life_months' => ['sometimes', 'integer', 'min:1'],
                'current_book_value' => ['nullable', 'numeric', 'min:0'],
                'status' => ['sometimes', 'in:active,disposed,fully_depreciated'],
            ],
            default => [],
        };
    }
}

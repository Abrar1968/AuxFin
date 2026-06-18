<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class ClientRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'store' => [
                'name' => ['required', 'string', 'max:200'],
                'email' => ['nullable', 'email', 'max:200'],
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string'],
                'contact_person' => ['nullable', 'string', 'max:150'],
            ],
            'update' => [
                'name' => ['sometimes', 'string', 'max:200'],
                'email' => ['nullable', 'email', 'max:200'],
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string'],
                'contact_person' => ['nullable', 'string', 'max:150'],
            ],
            default => [],
        };
    }
}

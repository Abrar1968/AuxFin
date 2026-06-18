<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\AuthorizedRequest;

class ChangePasskeyRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return [
            'current_passkey' => ['required', 'string'],
            'new_passkey' => ['required', 'string', 'min:8', 'max:20'],
        ];
    }
}

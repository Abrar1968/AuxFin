<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\AuthorizedRequest;

class LoginRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'passkey' => ['required', 'string', 'min:6'],
        ];
    }
}

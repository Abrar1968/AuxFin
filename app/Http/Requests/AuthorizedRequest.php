<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class AuthorizedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function actionMethod(): string
    {
        return (string) ($this->route()?->getActionMethod() ?? '');
    }

    /**
     * @param array<int, string> $preferredNames
     */
    protected function routeParameterInt(array $preferredNames = ['id']): ?int
    {
        foreach ($preferredNames as $name) {
            $value = $this->route($name);
            if ($value !== null) {
                return is_object($value) && method_exists($value, 'getKey')
                    ? (int) $value->getKey()
                    : (int) $value;
            }
        }

        foreach (($this->route()?->parameters() ?? []) as $value) {
            if ($value !== null) {
                return is_object($value) && method_exists($value, 'getKey')
                    ? (int) $value->getKey()
                    : (int) $value;
            }
        }

        return null;
    }
}

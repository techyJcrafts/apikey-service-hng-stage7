<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'in:deposit,transfer,read'],
            'expiry' => ['required', 'string', 'regex:/^[1-9]\d*[HDMY]$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'expiry.regex' => 'Expiry must be in format: 1H, 1D, 1M, or 1Y',
            'permissions.*.in' => 'Permission must be one of: deposit, transfer, read',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RolloverKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expired_key_id' => ['required', 'string', 'exists:api_keys,id'],
            'expiry' => ['required', 'string', 'regex:/^[1-9]\d*[HDMY]$/'],
        ];
    }
}

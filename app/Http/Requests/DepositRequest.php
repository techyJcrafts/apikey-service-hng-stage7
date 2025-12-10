<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:100', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Minimum deposit is ₦100',
            'amount.max' => 'Maximum deposit is ₦10,000,000',
        ];
    }

    /**
     * Get validated amount (returned as string for decimal precision)
     */
    public function getAmount(): string
    {
        return (string) $this->validated()['amount'];
    }
}

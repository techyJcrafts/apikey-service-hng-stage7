<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_number' => ['required', 'string', 'size:14', 'exists:wallets,wallet_number'],
            'amount' => ['required', 'numeric', 'min:10', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_number.exists' => 'Recipient wallet not found',
            'amount.min' => 'Minimum transfer is ₦10',
            'amount.max' => 'Maximum transfer is ₦10,000,000',
        ];
    }

    public function getAmount(): string
    {
        return (string) $this->validated()['amount'];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:10|max:5000',
            'payment_gateway' => 'required|string|max:191',
            'manual_payment_image' => 'required_if:payment_gateway,manual_payment|nullable|file|mimes:jpg,jpeg,png,svg,pdf|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Deposit amount is required',
            'amount.min' => 'Minimum deposit amount is 10',
            'amount.max' => 'Maximum deposit amount is 5000',
            'payment_gateway.required' => 'Payment gateway is required',
            'manual_payment_image.required_if' => 'Payment proof image is required for manual payment',
            'manual_payment_image.mimes' => 'Payment proof must be jpg, jpeg, png, svg or pdf',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveManualPaymentRequest extends FormRequest
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
            'payment_status' => 'required|in:completed,rejected',
            'admin_note' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_status.required' => 'Payment status is required',
            'payment_status.in' => 'Payment status must be either completed or rejected',
        ];
    }
}

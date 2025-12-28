<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
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
            'coupon_code' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'coupon_code.required' => __('Coupon code is required'),
            'coupon_code.max' => __('Coupon code is too long'),
        ];
    }
}

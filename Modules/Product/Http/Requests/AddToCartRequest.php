<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_inventory_details,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'options' => ['nullable', 'array'],
            'options.color_id' => ['nullable', 'integer'],
            'options.size_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => __('Product ID is required'),
            'product_id.exists' => __('Product not found'),
            'variant_id.exists' => __('Product variant not found'),
            'quantity.min' => __('Quantity must be at least 1'),
            'quantity.max' => __('Quantity cannot exceed 9999'),
        ];
    }
}

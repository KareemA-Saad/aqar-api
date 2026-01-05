<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVariantRequest extends FormRequest
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
            'color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'additional_price' => ['nullable', 'numeric', 'min:0'],
            'add_cost' => ['nullable', 'numeric', 'min:0'],
            'image_id' => ['nullable', 'integer'],
            'stock_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'color_id.exists' => __('Selected color does not exist'),
            'size_id.exists' => __('Selected size does not exist'),
            'additional_price.min' => __('Additional price cannot be negative'),
            'stock_count.min' => __('Stock count cannot be negative'),
        ];
    }
}

<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartAddressRequest extends FormRequest
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
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.phone' => ['required_with:shipping_address', 'string', 'max:20'],
            'shipping_address.email' => ['nullable', 'email', 'max:255'],
            'shipping_address.address' => ['required_with:shipping_address', 'string', 'max:500'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.state' => ['nullable', 'string', 'max:100'],
            'shipping_address.country_id' => ['required_with:shipping_address', 'integer'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:20'],

            'billing_address' => ['nullable', 'array'],
            'billing_address.name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.phone' => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.email' => ['nullable', 'email', 'max:255'],
            'billing_address.address' => ['required_with:billing_address', 'string', 'max:500'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.state' => ['nullable', 'string', 'max:100'],
            'billing_address.country_id' => ['required_with:billing_address', 'integer'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],

            'same_as_shipping' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'shipping_address.name.required_with' => __('Shipping name is required'),
            'shipping_address.phone.required_with' => __('Shipping phone is required'),
            'shipping_address.address.required_with' => __('Shipping address is required'),
            'shipping_address.city.required_with' => __('Shipping city is required'),
            'shipping_address.country_id.required_with' => __('Shipping country is required'),
            'billing_address.name.required_with' => __('Billing name is required'),
            'billing_address.phone.required_with' => __('Billing phone is required'),
            'billing_address.address.required_with' => __('Billing address is required'),
            'billing_address.city.required_with' => __('Billing city is required'),
            'billing_address.country_id.required_with' => __('Billing country is required'),
        ];
    }
}

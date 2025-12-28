<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Checkout Request
 *
 * Validates checkout form data including shipping address and payment method.
 */
#[OA\Schema(
    schema: 'CheckoutRequest',
    title: 'Checkout Request',
    description: 'Checkout form validation schema',
    required: ['name', 'email', 'phone', 'address', 'city', 'country_id', 'shipping_method_id', 'payment_method'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'phone', type: 'string', maxLength: 50, example: '+1234567890'),
        new OA\Property(property: 'address', type: 'string', maxLength: 500, example: '123 Main Street'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, example: 'New York'),
        new OA\Property(property: 'state_id', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'country_id', type: 'integer', example: 1),
        new OA\Property(property: 'zipcode', type: 'string', maxLength: 20, nullable: true, example: '10001'),
        new OA\Property(property: 'shipping_method_id', type: 'integer', example: 1),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['stripe', 'paypal', 'cod'], example: 'stripe'),
        new OA\Property(property: 'notes', type: 'string', maxLength: 1000, nullable: true, example: 'Please leave at the door'),
        new OA\Property(property: 'payment_token', type: 'string', nullable: true, description: 'Payment token from Stripe/PayPal frontend SDK'),
    ]
)]
class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Cart-based checkout, authorization handled by cart ownership
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state_id' => 'nullable|integer|exists:states,id',
            'country_id' => 'required|integer|exists:countries,id',
            'zipcode' => 'nullable|string|max:20',
            'shipping_method_id' => 'required|integer|exists:shipping_methods,id',
            'payment_method' => 'required|string|in:stripe,paypal,cod',
            'notes' => 'nullable|string|max:1000',
        ];

        // Payment token required for digital payments
        if ($this->input('payment_method') !== 'cod') {
            $rules['payment_token'] = 'required_if:payment_method,stripe,paypal|string';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your full name',
            'email.required' => 'Please provide your email address',
            'email.email' => 'Please provide a valid email address',
            'phone.required' => 'Please provide your phone number',
            'address.required' => 'Please provide your delivery address',
            'city.required' => 'Please provide your city',
            'country_id.required' => 'Please select your country',
            'country_id.exists' => 'The selected country is invalid',
            'state_id.exists' => 'The selected state is invalid',
            'shipping_method_id.required' => 'Please select a shipping method',
            'shipping_method_id.exists' => 'The selected shipping method is invalid',
            'payment_method.required' => 'Please select a payment method',
            'payment_method.in' => 'The selected payment method is not supported',
            'payment_token.required_if' => 'Payment token is required for online payments',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'phone' => 'phone number',
            'address' => 'delivery address',
            'city' => 'city',
            'state_id' => 'state',
            'country_id' => 'country',
            'zipcode' => 'zip code',
            'shipping_method_id' => 'shipping method',
            'payment_method' => 'payment method',
            'notes' => 'order notes',
            'payment_token' => 'payment token',
        ];
    }
}

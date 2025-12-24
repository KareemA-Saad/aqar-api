<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Customer;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for managing customer addresses.
 */
#[OA\Schema(
    schema: 'AddressRequest',
    title: 'Address Request',
    description: 'Request body for creating or updating a customer address',
    required: ['address_line_1', 'city', 'country'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191, description: 'Recipient name'),
        new OA\Property(property: 'phone', type: 'string', example: '+1234567890', maxLength: 20, description: 'Contact phone'),
        new OA\Property(property: 'address_line_1', type: 'string', example: '123 Main Street', maxLength: 500),
        new OA\Property(property: 'address_line_2', type: 'string', example: 'Apt 4B', nullable: true, maxLength: 500),
        new OA\Property(property: 'city', type: 'string', example: 'New York', maxLength: 100),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true, maxLength: 100),
        new OA\Property(property: 'postal_code', type: 'string', example: '10001', nullable: true, maxLength: 20),
        new OA\Property(property: 'country', type: 'string', example: 'USA', maxLength: 100),
        new OA\Property(property: 'is_default', type: 'boolean', example: false, description: 'Set as default address'),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['shipping', 'billing', 'both'],
            example: 'shipping',
            description: 'Address type'
        ),
    ]
)]
final class AddressRequest extends FormRequest
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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address_line_1' => ['required', 'string', 'max:500'],
            'address_line_2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'in:shipping,billing,both'],
        ];

        // Make fields optional on update
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['address_line_1'] = ['sometimes', 'string', 'max:500'];
            $rules['city'] = ['sometimes', 'string', 'max:100'];
            $rules['country'] = ['sometimes', 'string', 'max:100'];
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
            'address_line_1.required' => 'Address line 1 is required.',
            'city.required' => 'City is required.',
            'country.required' => 'Country is required.',
            'type.in' => 'Address type must be shipping, billing, or both.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'address_line_1' => 'street address',
            'address_line_2' => 'apartment/suite',
            'postal_code' => 'postal/ZIP code',
            'is_default' => 'default address',
        ];
    }
}

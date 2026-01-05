<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateBookingRequest',
    title: 'Create Booking Request',
    description: 'Request to create a hotel booking',
    required: ['hold_token', 'email', 'mobile', 'country_id', 'post_code'],
    properties: [
        new OA\Property(property: 'hold_token', type: 'string', example: 'abc123xyz789...', description: 'Token from init booking'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'guest@example.com'),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'street', type: 'string', example: '123 Main Street'),
        new OA\Property(property: 'city', type: 'string', example: 'New York'),
        new OA\Property(property: 'state_id', type: 'integer', example: 1),
        new OA\Property(property: 'country_id', type: 'integer', example: 1),
        new OA\Property(property: 'post_code', type: 'string', example: '10001'),
        new OA\Property(property: 'notes', type: 'string', example: 'Late check-in requested'),
        new OA\Property(
            property: 'meal_options',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'breakfast', type: 'boolean', example: true),
                    new OA\Property(property: 'lunch', type: 'boolean', example: false),
                    new OA\Property(property: 'dinner', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe'),
    ]
)]
class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hold_token' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'street' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'post_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'meal_options' => ['nullable', 'array'],
            'meal_options.*.room_type_id' => ['required_with:meal_options', 'integer'],
            'meal_options.*.breakfast' => ['nullable', 'boolean'],
            'meal_options.*.lunch' => ['nullable', 'boolean'],
            'meal_options.*.dinner' => ['nullable', 'boolean'],
            'payment_gateway' => ['nullable', 'string', 'in:stripe,paypal,cod'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hold_token.required' => 'Booking hold token is required. Please start the booking process again.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'mobile.required' => 'Mobile number is required.',
            'country_id.required' => 'Country is required.',
            'country_id.exists' => 'Selected country does not exist.',
            'post_code.required' => 'Postal code is required.',
        ];
    }
}

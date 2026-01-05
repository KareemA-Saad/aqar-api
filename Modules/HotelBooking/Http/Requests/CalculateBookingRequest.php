<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CalculateBookingRequest',
    title: 'Calculate Booking Request',
    description: 'Request to calculate booking total with taxes',
    required: ['hold_token'],
    properties: [
        new OA\Property(property: 'hold_token', type: 'string', example: 'abc123xyz789...'),
        new OA\Property(property: 'country_id', type: 'integer', example: 1),
        new OA\Property(property: 'state_id', type: 'integer', example: 1),
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
        new OA\Property(property: 'coupon_code', type: 'string', example: 'SUMMER2024'),
    ]
)]
class CalculateBookingRequest extends FormRequest
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
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'meal_options' => ['nullable', 'array'],
            'meal_options.*.room_type_id' => ['required_with:meal_options', 'integer'],
            'meal_options.*.breakfast' => ['nullable', 'boolean'],
            'meal_options.*.lunch' => ['nullable', 'boolean'],
            'meal_options.*.dinner' => ['nullable', 'boolean'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hold_token.required' => 'Booking hold token is required.',
        ];
    }
}

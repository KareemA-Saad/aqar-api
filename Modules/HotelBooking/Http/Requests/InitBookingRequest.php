<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InitBookingRequest',
    title: 'Initialize Booking Request',
    description: 'Request to start booking process and hold rooms',
    required: ['room_types', 'check_in', 'check_out'],
    properties: [
        new OA\Property(
            property: 'room_types',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'quantity', type: 'integer', example: 2),
                    new OA\Property(property: 'adults', type: 'integer', example: 2),
                    new OA\Property(property: 'children', type: 'integer', example: 1),
                ],
                type: 'object'
            )
        ),
        new OA\Property(property: 'check_in', type: 'string', format: 'date', example: '2024-03-15'),
        new OA\Property(property: 'check_out', type: 'string', format: 'date', example: '2024-03-18'),
        new OA\Property(property: 'hotel_id', type: 'integer', example: 1),
    ]
)]
class InitBookingRequest extends FormRequest
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
            'room_types' => ['required', 'array', 'min:1'],
            'room_types.*.room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'room_types.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'room_types.*.adults' => ['nullable', 'integer', 'min:1'],
            'room_types.*.children' => ['nullable', 'integer', 'min:0'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'hotel_id' => ['required', 'integer', 'exists:hotels,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_types.required' => 'At least one room type is required.',
            'room_types.*.room_type_id.required' => 'Room type ID is required.',
            'room_types.*.room_type_id.exists' => 'Selected room type does not exist.',
            'room_types.*.quantity.required' => 'Room quantity is required.',
            'room_types.*.quantity.min' => 'Room quantity must be at least 1.',
            'check_in.required' => 'Check-in date is required.',
            'check_in.after_or_equal' => 'Check-in date cannot be in the past.',
            'check_out.required' => 'Check-out date is required.',
            'check_out.after' => 'Check-out date must be after check-in date.',
            'hotel_id.required' => 'Hotel is required.',
            'hotel_id.exists' => 'Selected hotel does not exist.',
        ];
    }
}

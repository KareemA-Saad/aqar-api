<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreRoomTypeRequest',
    title: 'Store Room Type Request',
    required: ['name', 'hotel_id', 'max_guest', 'base_charge'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Deluxe Suite'),
        new OA\Property(property: 'description', type: 'string', example: 'Spacious suite with city view'),
        new OA\Property(property: 'hotel_id', type: 'integer', example: 1),
        new OA\Property(property: 'max_guest', type: 'integer', example: 4),
        new OA\Property(property: 'max_adult', type: 'integer', example: 2),
        new OA\Property(property: 'max_child', type: 'integer', example: 2),
        new OA\Property(property: 'no_bedroom', type: 'integer', example: 1),
        new OA\Property(property: 'no_living_room', type: 'integer', example: 1),
        new OA\Property(property: 'no_bathrooms', type: 'integer', example: 1),
        new OA\Property(property: 'base_charge', type: 'number', example: 199.99),
        new OA\Property(property: 'extra_adult', type: 'number', example: 50.00),
        new OA\Property(property: 'extra_child', type: 'number', example: 25.00),
        new OA\Property(property: 'breakfast_price', type: 'number', example: 15.00),
        new OA\Property(property: 'lunch_price', type: 'number', example: 20.00),
        new OA\Property(property: 'dinner_price', type: 'number', example: 25.00),
        new OA\Property(property: 'bed_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'extra_bed_type_id', type: 'integer', example: 2),
        new OA\Property(property: 'amenity_ids', type: 'array', items: new OA\Items(type: 'integer')),
    ]
)]
class StoreRoomTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'hotel_id' => ['required', 'integer', 'exists:hotels,id'],
            'max_guest' => ['required', 'integer', 'min:1', 'max:50'],
            'max_adult' => ['nullable', 'integer', 'min:1', 'max:50'],
            'max_child' => ['nullable', 'integer', 'min:0', 'max:20'],
            'no_bedroom' => ['nullable', 'integer', 'min:0', 'max:20'],
            'no_living_room' => ['nullable', 'integer', 'min:0', 'max:10'],
            'no_bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'base_charge' => ['required', 'numeric', 'min:0'],
            'extra_adult' => ['nullable', 'numeric', 'min:0'],
            'extra_child' => ['nullable', 'numeric', 'min:0'],
            'breakfast_price' => ['nullable', 'numeric', 'min:0'],
            'lunch_price' => ['nullable', 'numeric', 'min:0'],
            'dinner_price' => ['nullable', 'numeric', 'min:0'],
            'bed_type_id' => ['nullable', 'integer', 'exists:bed_types,id'],
            'extra_bed_type_id' => ['nullable', 'integer', 'exists:bed_types,id'],
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Room type name is required.',
            'hotel_id.required' => 'Hotel is required.',
            'hotel_id.exists' => 'Selected hotel does not exist.',
            'max_guest.required' => 'Maximum guest count is required.',
            'base_charge.required' => 'Base charge is required.',
        ];
    }
}

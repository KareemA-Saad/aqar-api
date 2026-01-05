<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SearchRoomRequest',
    title: 'Search Room Request',
    required: ['check_in', 'check_out'],
    properties: [
        new OA\Property(property: 'check_in', type: 'string', format: 'date', example: '2024-03-15'),
        new OA\Property(property: 'check_out', type: 'string', format: 'date', example: '2024-03-18'),
        new OA\Property(property: 'adults', type: 'integer', example: 2),
        new OA\Property(property: 'children', type: 'integer', example: 1),
        new OA\Property(property: 'rooms', type: 'integer', example: 1),
        new OA\Property(property: 'hotel_id', type: 'integer', example: 1),
        new OA\Property(property: 'state_id', type: 'integer', example: 1),
        new OA\Property(property: 'country_id', type: 'integer', example: 1),
        new OA\Property(property: 'min_price', type: 'number', example: 50.00),
        new OA\Property(property: 'max_price', type: 'number', example: 500.00),
        new OA\Property(property: 'amenity_ids', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'keyword', type: 'string', example: 'ocean view'),
        new OA\Property(property: 'sort_by', type: 'string', example: 'price', enum: ['price', 'rating', 'name']),
        new OA\Property(property: 'sort_order', type: 'string', example: 'asc', enum: ['asc', 'desc']),
    ]
)]
class SearchRoomRequest extends FormRequest
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
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:10'],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:10'],
            'hotel_id' => ['nullable', 'integer', 'exists:hotels,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:price,rating,name'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_in.required' => 'Check-in date is required.',
            'check_in.after_or_equal' => 'Check-in date cannot be in the past.',
            'check_out.required' => 'Check-out date is required.',
            'check_out.after' => 'Check-out date must be after check-in date.',
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',
        ];
    }

    /**
     * Get default values for optional parameters.
     */
    public function getAdults(): int
    {
        return (int) ($this->input('adults') ?? 1);
    }

    public function getChildren(): int
    {
        return (int) ($this->input('children') ?? 0);
    }

    public function getRooms(): int
    {
        return (int) ($this->input('rooms') ?? 1);
    }
}

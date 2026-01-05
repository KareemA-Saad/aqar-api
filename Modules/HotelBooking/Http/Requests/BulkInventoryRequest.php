<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkInventoryRequest',
    title: 'Bulk Inventory Request',
    description: 'Request to create/update inventory for a date range',
    required: ['room_type_id', 'start_date', 'end_date', 'total_room'],
    properties: [
        new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2024-03-01'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2024-03-31'),
        new OA\Property(property: 'total_room', type: 'integer', example: 10),
        new OA\Property(property: 'available_room', type: 'integer', example: 10),
        new OA\Property(property: 'extra_base_charge', type: 'number', example: 249.99),
        new OA\Property(property: 'extra_adult', type: 'number', example: 50.00),
        new OA\Property(property: 'extra_child', type: 'number', example: 25.00),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'days_of_week', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3, 4, 5], description: '1=Monday, 7=Sunday. If empty, applies to all days.'),
    ]
)]
class BulkInventoryRequest extends FormRequest
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
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_room' => ['required', 'integer', 'min:0'],
            'available_room' => ['nullable', 'integer', 'min:0'],
            'extra_base_charge' => ['nullable', 'numeric', 'min:0'],
            'extra_adult' => ['nullable', 'numeric', 'min:0'],
            'extra_child' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'min:1', 'max:7'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_type_id.required' => 'Room type is required.',
            'room_type_id.exists' => 'Selected room type does not exist.',
            'start_date.required' => 'Start date is required.',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
            'total_room.required' => 'Total room count is required.',
        ];
    }
}

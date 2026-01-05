<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateInventoryRequest',
    title: 'Update Inventory Request',
    description: 'Request to update room type inventory for a date',
    properties: [
        new OA\Property(property: 'total_room', type: 'integer', example: 10),
        new OA\Property(property: 'available_room', type: 'integer', example: 7),
        new OA\Property(property: 'extra_base_charge', type: 'number', example: 249.99),
        new OA\Property(property: 'extra_adult', type: 'number', example: 50.00),
        new OA\Property(property: 'extra_child', type: 'number', example: 25.00),
        new OA\Property(property: 'status', type: 'boolean', example: true),
    ]
)]
class UpdateInventoryRequest extends FormRequest
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
            'total_room' => ['sometimes', 'integer', 'min:0'],
            'available_room' => ['sometimes', 'integer', 'min:0'],
            'extra_base_charge' => ['nullable', 'numeric', 'min:0'],
            'extra_adult' => ['nullable', 'numeric', 'min:0'],
            'extra_child' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}

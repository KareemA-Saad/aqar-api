<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreRoomRequest',
    title: 'Store Room Request',
    required: ['name', 'room_type_id'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Room 101'),
        new OA\Property(property: 'description', type: 'string', example: 'Corner room with ocean view'),
        new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'base_cost', type: 'number', example: 199.99),
        new OA\Property(property: 'share_value', type: 'string', example: 'private'),
        new OA\Property(property: 'location', type: 'string', example: 'Floor 1, Wing A'),
        new OA\Property(property: 'type', type: 'string', example: 'standard'),
        new OA\Property(property: 'duration', type: 'string', example: 'nightly'),
        new OA\Property(property: 'is_featured', type: 'boolean', example: false),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'country_id', type: 'integer', example: 1),
        new OA\Property(property: 'state_id', type: 'integer', example: 1),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string')),
    ]
)]
class StoreRoomRequest extends FormRequest
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
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'base_cost' => ['nullable', 'numeric', 'min:0'],
            'share_value' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Room name is required.',
            'room_type_id.required' => 'Room type is required.',
            'room_type_id.exists' => 'Selected room type does not exist.',
        ];
    }
}

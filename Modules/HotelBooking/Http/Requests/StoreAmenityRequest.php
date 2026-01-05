<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreAmenityRequest',
    title: 'Store Amenity Request',
    description: 'Request to create an amenity',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Free WiFi'),
        new OA\Property(property: 'icon', type: 'string', example: 'wifi'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
    ]
)]
class StoreAmenityRequest extends FormRequest
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
            'icon' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Amenity name is required.',
        ];
    }
}

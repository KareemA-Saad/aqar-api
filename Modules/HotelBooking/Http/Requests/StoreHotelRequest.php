<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreHotelRequest',
    title: 'Store Hotel Request',
    required: ['name', 'location'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Grand Hotel'),
        new OA\Property(property: 'slug', type: 'string', example: 'grand-hotel', nullable: true),
        new OA\Property(property: 'location', type: 'string', example: '123 Main Street, Downtown'),
        new OA\Property(property: 'about', type: 'string', example: 'Luxury hotel with modern amenities'),
        new OA\Property(property: 'distance', type: 'string', example: '5 km from city center'),
        new OA\Property(property: 'restaurant_inside', type: 'boolean', example: true),
        new OA\Property(property: 'country_id', type: 'integer', example: 1),
        new OA\Property(property: 'state_id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'amenity_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string'), description: 'Array of image URLs or IDs'),
    ]
)]
class StoreHotelRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', 'unique:hotels,slug'],
            'location' => ['required', 'string', 'max:500'],
            'about' => ['nullable', 'string'],
            'distance' => ['nullable', 'string', 'max:255'],
            'restaurant_inside' => ['nullable', 'boolean'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'status' => ['nullable', 'boolean'],
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
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
            'name.required' => 'Hotel name is required.',
            'location.required' => 'Hotel location is required.',
            'slug.unique' => 'This slug is already taken.',
            'country_id.exists' => 'Selected country does not exist.',
            'state_id.exists' => 'Selected state does not exist.',
            'amenity_ids.*.exists' => 'One or more selected amenities do not exist.',
        ];
    }
}

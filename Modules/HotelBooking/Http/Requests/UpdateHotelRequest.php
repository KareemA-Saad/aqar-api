<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateHotelRequest',
    title: 'Update Hotel Request',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Grand Hotel'),
        new OA\Property(property: 'slug', type: 'string', example: 'grand-hotel'),
        new OA\Property(property: 'location', type: 'string', example: '123 Main Street, Downtown'),
        new OA\Property(property: 'about', type: 'string', example: 'Luxury hotel with modern amenities'),
        new OA\Property(property: 'distance', type: 'string', example: '5 km from city center'),
        new OA\Property(property: 'restaurant_inside', type: 'boolean', example: true),
        new OA\Property(property: 'country_id', type: 'integer', example: 1),
        new OA\Property(property: 'state_id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'amenity_ids', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string')),
    ]
)]
class UpdateHotelRequest extends FormRequest
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
        $hotelId = $this->route('id') ?? $this->route('hotel');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('hotels', 'slug')->ignore($hotelId)],
            'location' => ['sometimes', 'string', 'max:500'],
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
}

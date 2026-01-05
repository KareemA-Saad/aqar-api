<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreReviewRequest',
    title: 'Store Review Request',
    description: 'Request to add a hotel/room review',
    required: ['rating', 'description'],
    properties: [
        new OA\Property(property: 'rating', type: 'number', example: 4.5),
        new OA\Property(property: 'cleanliness', type: 'integer', example: 5, minimum: 1, maximum: 5),
        new OA\Property(property: 'comfort', type: 'integer', example: 4, minimum: 1, maximum: 5),
        new OA\Property(property: 'staff', type: 'integer', example: 5, minimum: 1, maximum: 5),
        new OA\Property(property: 'facilities', type: 'integer', example: 4, minimum: 1, maximum: 5),
        new OA\Property(property: 'description', type: 'string', example: 'Excellent stay! The staff was very helpful.'),
        new OA\Property(property: 'room_id', type: 'integer', example: 1, nullable: true),
    ]
)]
class StoreReviewRequest extends FormRequest
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
            'rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'cleanliness' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comfort' => ['nullable', 'integer', 'min:1', 'max:5'],
            'staff' => ['nullable', 'integer', 'min:1', 'max:5'],
            'facilities' => ['nullable', 'integer', 'min:1', 'max:5'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Rating is required.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating cannot exceed 5.',
            'description.required' => 'Review description is required.',
            'description.min' => 'Review must be at least 10 characters.',
            'description.max' => 'Review cannot exceed 2000 characters.',
        ];
    }
}

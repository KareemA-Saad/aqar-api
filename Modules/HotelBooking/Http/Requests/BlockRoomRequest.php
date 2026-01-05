<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BlockRoomRequest',
    title: 'Block Room Request',
    description: 'Request to block a room for specific dates',
    required: ['start_date', 'end_date'],
    properties: [
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2024-03-15'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2024-03-18'),
        new OA\Property(property: 'reason', type: 'string', example: 'Maintenance'),
    ]
)]
class BlockRoomRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Start date is required.',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
        ];
    }
}

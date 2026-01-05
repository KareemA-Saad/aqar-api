<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateBookingStatusRequest',
    title: 'Update Booking Status Request',
    description: 'Request to update booking status',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'confirmed', enum: ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show']),
        new OA\Property(property: 'notes', type: 'string', example: 'Status updated by admin'),
    ]
)]
class UpdateBookingStatusRequest extends FormRequest
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
            'status' => ['required', 'string', 'in:pending,confirmed,checked_in,checked_out,cancelled,no_show'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

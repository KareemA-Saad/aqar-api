<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CancelBookingRequest',
    title: 'Cancel Booking Request',
    description: 'Request to cancel a booking',
    properties: [
        new OA\Property(property: 'reason', type: 'string', example: 'Change of travel plans'),
    ]
)]
class CancelBookingRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

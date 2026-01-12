<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Update Booking Status Request
 */
#[OA\Schema(
    schema: 'UpdateBookingStatusRequest',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'complete', 'cancelled', 'rejected']),
        new OA\Property(property: 'reason', type: 'string', nullable: true, description: 'Reason for status change'),
    ]
)]
final class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,confirmed,complete,cancelled,rejected'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}

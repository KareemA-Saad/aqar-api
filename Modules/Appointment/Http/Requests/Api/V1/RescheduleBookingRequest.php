<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Reschedule Booking Request
 */
#[OA\Schema(
    schema: 'RescheduleBookingRequest',
    required: ['appointment_date', 'appointment_time'],
    properties: [
        new OA\Property(property: 'appointment_date', type: 'string', format: 'date', example: '2024-01-20'),
        new OA\Property(property: 'appointment_time', type: 'string', example: '14:00'),
    ]
)]
final class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointment_time' => ['required', 'string'],
        ];
    }
}

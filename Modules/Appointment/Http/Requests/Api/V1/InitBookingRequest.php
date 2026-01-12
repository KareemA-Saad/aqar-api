<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Initialize Booking Request
 */
#[OA\Schema(
    schema: 'InitBookingRequest',
    required: ['appointment_date', 'appointment_time'],
    properties: [
        new OA\Property(property: 'appointment_date', type: 'string', format: 'date', example: '2024-01-15'),
        new OA\Property(property: 'appointment_time', type: 'string', example: '09:00'),
        new OA\Property(property: 'sub_appointment_id', type: 'integer', nullable: true),
        new OA\Property(
            property: 'additional_services',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'integer'),
            description: 'Array of additional service IDs'
        ),
    ]
)]
final class InitBookingRequest extends FormRequest
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
            'sub_appointment_id' => ['nullable', 'integer', 'exists:sub_appointments,id'],
            'additional_services' => ['nullable', 'array'],
            'additional_services.*' => ['integer', 'exists:additional_appointments,id'],
        ];
    }
}

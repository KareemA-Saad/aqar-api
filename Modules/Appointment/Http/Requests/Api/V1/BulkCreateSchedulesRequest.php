<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Bulk Create Schedules Request
 */
#[OA\Schema(
    schema: 'BulkCreateSchedulesRequest',
    required: ['appointment_id', 'slots'],
    properties: [
        new OA\Property(property: 'appointment_id', type: 'integer'),
        new OA\Property(
            property: 'slots',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'day_id', type: 'integer'),
                    new OA\Property(property: 'time', type: 'string', example: '09:00'),
                    new OA\Property(property: 'allow_multiple', type: 'boolean'),
                    new OA\Property(property: 'max_bookings', type: 'integer'),
                ],
                type: 'object'
            )
        ),
    ]
)]
final class BulkCreateSchedulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.day_id' => ['required', 'integer', 'exists:appointment_days,id'],
            'slots.*.time' => ['required', 'string'],
            'slots.*.allow_multiple' => ['nullable', 'boolean'],
            'slots.*.max_bookings' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

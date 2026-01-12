<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Store Schedule Request
 */
#[OA\Schema(
    schema: 'StoreScheduleRequest',
    required: ['appointment_id', 'appointment_day_id', 'time'],
    properties: [
        new OA\Property(property: 'appointment_id', type: 'integer'),
        new OA\Property(property: 'appointment_day_id', type: 'integer'),
        new OA\Property(property: 'time', type: 'string', example: '09:00'),
        new OA\Property(property: 'allow_multiple', type: 'boolean', default: false),
        new OA\Property(property: 'max_bookings', type: 'integer', minimum: 1, nullable: true),
        new OA\Property(property: 'is_blocked', type: 'boolean', default: false),
    ]
)]
final class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'appointment_day_id' => ['required', 'integer', 'exists:appointment_days,id'],
            'time' => ['required', 'string'],
            'allow_multiple' => ['nullable', 'boolean'],
            'max_bookings' => ['nullable', 'integer', 'min:1'],
            'is_blocked' => ['nullable', 'boolean'],
        ];
    }
}

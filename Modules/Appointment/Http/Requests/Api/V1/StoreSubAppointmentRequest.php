<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Store Sub-Appointment Request
 */
#[OA\Schema(
    schema: 'StoreSubAppointmentRequest',
    required: ['title', 'appointment_id', 'price'],
    properties: [
        new OA\Property(property: 'title', type: 'object', example: ['en' => 'Deep Cleaning', 'ar' => 'تنظيف عميق']),
        new OA\Property(property: 'description', type: 'object', nullable: true),
        new OA\Property(property: 'appointment_id', type: 'integer'),
        new OA\Property(property: 'slug', type: 'string', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', minimum: 0, example: 50.00),
        new OA\Property(property: 'duration', type: 'integer', nullable: true, description: 'Duration in minutes', example: 60),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1], default: 1),
    ]
)]
final class StoreSubAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:sub_appointments,slug'],
            'image' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}

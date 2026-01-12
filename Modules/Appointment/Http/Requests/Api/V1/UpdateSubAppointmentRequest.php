<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Update Sub-Appointment Request
 */
#[OA\Schema(
    schema: 'UpdateSubAppointmentRequest',
    properties: [
        new OA\Property(property: 'title', type: 'object', nullable: true),
        new OA\Property(property: 'description', type: 'object', nullable: true),
        new OA\Property(property: 'appointment_id', type: 'integer', nullable: true),
        new OA\Property(property: 'slug', type: 'string', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', minimum: 0, nullable: true),
        new OA\Property(property: 'duration', type: 'integer', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1], nullable: true),
    ]
)]
final class UpdateSubAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subAppointmentId = $this->route('subAppointment') ?? $this->route('id');

        return [
            'title' => ['sometimes', 'array'],
            'title.en' => ['required_with:title', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'appointment_id' => ['sometimes', 'integer', 'exists:appointments,id'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:sub_appointments,slug,' . $subAppointmentId],
            'image' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}

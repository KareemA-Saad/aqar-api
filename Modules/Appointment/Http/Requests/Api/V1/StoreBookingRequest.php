<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Store Booking Request
 */
#[OA\Schema(
    schema: 'StoreBookingRequest',
    required: ['appointment_date', 'appointment_time', 'name', 'email', 'phone', 'payment_gateway'],
    properties: [
        new OA\Property(property: 'appointment_date', type: 'string', format: 'date', example: '2024-01-15'),
        new OA\Property(property: 'appointment_time', type: 'string', example: '09:00'),
        new OA\Property(property: 'sub_appointment_id', type: 'integer', nullable: true),
        new OA\Property(
            property: 'additional_services',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'integer')
        ),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'note', type: 'string', nullable: true, description: 'Special instructions or notes'),
        new OA\Property(property: 'person_count', type: 'integer', minimum: 1, default: 1, description: 'Number of persons (for group appointments)'),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe'),
        new OA\Property(property: 'coupon_code', type: 'string', nullable: true),
    ]
)]
final class StoreBookingRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
            'person_count' => ['nullable', 'integer', 'min:1'],
            'payment_gateway' => ['required', 'string', 'max:50'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.required' => 'Please select an appointment date.',
            'appointment_date.after_or_equal' => 'The appointment date must be today or in the future.',
            'appointment_time.required' => 'Please select an appointment time.',
            'name.required' => 'Please provide your name.',
            'email.required' => 'Please provide your email address.',
            'email.email' => 'Please provide a valid email address.',
            'phone.required' => 'Please provide your phone number.',
            'payment_gateway.required' => 'Please select a payment method.',
        ];
    }
}

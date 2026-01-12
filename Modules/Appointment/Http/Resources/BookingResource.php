<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Booking Resource
 */
#[OA\Schema(
    schema: 'BookingResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'TXN-12345678'),
        new OA\Property(property: 'appointment_id', type: 'integer'),
        new OA\Property(property: 'sub_appointment_id', type: 'integer', nullable: true),
        new OA\Property(property: 'user_id', type: 'integer', nullable: true),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'appointment_date', type: 'string', format: 'date', example: '2024-01-15'),
        new OA\Property(property: 'appointment_time', type: 'string', example: '09:00'),
        new OA\Property(property: 'person_count', type: 'integer', example: 1),
        new OA\Property(property: 'sub_total', type: 'number', example: 100.00),
        new OA\Property(property: 'tax_amount', type: 'number', example: 10.00),
        new OA\Property(property: 'coupon_amount', type: 'number', example: 0),
        new OA\Property(property: 'total_amount', type: 'number', example: 110.00),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe'),
        new OA\Property(property: 'payment_status', type: 'string', enum: ['pending', 'complete', 'failed']),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'complete', 'cancelled', 'rejected']),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'cancellation_reason', type: 'string', nullable: true),
        new OA\Property(property: 'appointment', ref: '#/components/schemas/AppointmentResource', nullable: true),
        new OA\Property(property: 'sub_appointment', ref: '#/components/schemas/SubAppointmentResource', nullable: true),
        new OA\Property(property: 'additional_services', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'appointment_id' => $this->appointment_id,
            'sub_appointment_id' => $this->sub_appointment_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'appointment_date' => $this->appointment_date,
            'appointment_time' => $this->appointment_time,
            'person_count' => $this->person_count ?? 1,
            'sub_total' => (float) ($this->sub_total ?? 0),
            'tax_amount' => (float) ($this->tax_amount ?? 0),
            'coupon_amount' => (float) ($this->coupon_amount ?? 0),
            'total_amount' => (float) ($this->total_amount ?? 0),
            'payment_gateway' => $this->payment_gateway,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'note' => $this->note,
            'cancellation_reason' => $this->cancellation_reason,
            'appointment' => $this->whenLoaded('appointment', fn () => new AppointmentResource($this->appointment)),
            'sub_appointment' => $this->whenLoaded('subAppointment', fn () => new SubAppointmentResource($this->subAppointment)),
            'additional_services' => $this->whenLoaded('additionalLogs', fn () => $this->additionalLogs->map(fn ($log) => [
                'id' => $log->id,
                'service_id' => $log->additional_appointment_id,
                'title' => $log->title,
                'price' => (float) $log->price,
            ])),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

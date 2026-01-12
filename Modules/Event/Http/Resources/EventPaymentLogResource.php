<?php

declare(strict_types=1);

namespace Modules\Event\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Event Payment Log / Booking Resource for API responses.
 *
 * @mixin \Modules\Event\Entities\EventPaymentLog
 */
#[OA\Schema(
    schema: 'EventPaymentLogResource',
    title: 'Event Payment Log / Booking Resource',
    description: 'Event booking and payment log resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'event_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 5, nullable: true),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'TXN-12345ABC', nullable: true),
        new OA\Property(property: 'ticket_code', type: 'string', example: 'TKT-ABC12345', nullable: true),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St, City', nullable: true),
        new OA\Property(property: 'ticket_qty', type: 'integer', example: 2),
        new OA\Property(property: 'amount', type: 'number', format: 'double', example: 199.98),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'test', nullable: true),
        new OA\Property(property: 'track', type: 'string', example: 'TRACK123', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'check_in_status', type: 'boolean', example: false),
        new OA\Property(property: 'check_in_at', type: 'string', format: 'date-time', example: '2024-01-01T10:30:00.000000Z', nullable: true),
        new OA\Property(property: 'manual_payment_attachment', type: 'string', nullable: true),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'event', ref: '#/components/schemas/EventResource', nullable: true),
        new OA\Property(property: 'user', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 5),
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class EventPaymentLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'transaction_id' => $this->transaction_id,
            'ticket_code' => $this->ticket_code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'ticket_qty' => (int) $this->ticket_qty,
            'amount' => (float) $this->amount,
            'payment_gateway' => $this->payment_gateway,
            'track' => $this->track,
            'status' => (bool) $this->status,
            'check_in_status' => (bool) $this->check_in_status,
            'check_in_at' => $this->check_in_at?->toISOString(),
            'manual_payment_attachment' => $this->manual_payment_attachment,
            'note' => $this->note,
            'event' => $this->whenLoaded('event', function () {
                return new EventResource($this->event);
            }),
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ] : null;
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

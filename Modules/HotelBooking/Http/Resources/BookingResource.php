<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Booking Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\BookingInformation
 */
#[OA\Schema(
    schema: 'BookingResource',
    title: 'Booking Resource',
    description: 'Hotel booking representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'reservation_id', type: 'string', example: 'HBK-2024-001234'),
        new OA\Property(property: 'hotel', ref: '#/components/schemas/HotelResource', nullable: true),
        new OA\Property(property: 'room_type', ref: '#/components/schemas/RoomTypeResource', nullable: true),
        new OA\Property(property: 'room_types', type: 'array', items: new OA\Items(ref: '#/components/schemas/BookingRoomTypeResource'), description: 'Multi-room booking details'),
        new OA\Property(property: 'guest', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
            new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'email', type: 'string', example: 'guest@example.com'),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'address', properties: [
            new OA\Property(property: 'street', type: 'string', example: '123 Main St'),
            new OA\Property(property: 'city', type: 'string', example: 'New York'),
            new OA\Property(property: 'post_code', type: 'string', example: '10001'),
            new OA\Property(property: 'country', type: 'string', example: 'United States'),
            new OA\Property(property: 'state', type: 'string', example: 'NY'),
        ], type: 'object'),
        new OA\Property(property: 'booking_date', type: 'string', format: 'date', example: '2024-03-15'),
        new OA\Property(property: 'booking_expiry_date', type: 'string', format: 'date', example: '2024-03-18'),
        new OA\Property(property: 'check_in_time', type: 'string', example: '15:00:00'),
        new OA\Property(property: 'check_out_time', type: 'string', example: '11:00:00'),
        new OA\Property(property: 'nights', type: 'integer', example: 3),
        new OA\Property(property: 'booking_status', type: 'string', example: 'confirmed', enum: ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show']),
        new OA\Property(property: 'payment_status', type: 'string', example: 'paid', enum: ['pending', 'paid', 'partial', 'refunded', 'failed']),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe', nullable: true),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'txn_123456', nullable: true),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 599.97),
        new OA\Property(property: 'notes', type: 'string', example: 'Late check-in requested', nullable: true),
        new OA\Property(property: 'cancellation_policy', ref: '#/components/schemas/CancellationPolicyResource', nullable: true),
        new OA\Property(property: 'refund', properties: [
            new OA\Property(property: 'status', type: 'string', example: 'completed', nullable: true),
            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 299.99, nullable: true),
            new OA\Property(property: 'transaction_id', type: 'string', nullable: true),
            new OA\Property(property: 'processed_at', type: 'string', format: 'date-time', nullable: true),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'cancellation', properties: [
            new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'reason', type: 'string', nullable: true),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'check_in_out', properties: [
            new OA\Property(property: 'checked_in_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'checked_out_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'can_check_in', type: 'boolean', example: true),
            new OA\Property(property: 'can_check_out', type: 'boolean', example: false),
            new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
        ], type: 'object'),
        new OA\Property(property: 'payment_log', properties: [
            new OA\Property(property: 'total_amount', type: 'number', format: 'float'),
            new OA\Property(property: 'tax_amount', type: 'number', format: 'float'),
            new OA\Property(property: 'subtotal', type: 'number', format: 'float'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class BookingResource extends JsonResource
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
            'reservation_id' => $this->reservation_id,
            'hotel' => $this->whenLoaded('hotel', fn() => new HotelResource($this->hotel)),
            'room_type' => $this->whenLoaded('roomType', fn() => new RoomTypeResource($this->roomType)),
            'room_types' => BookingRoomTypeResource::collection($this->whenLoaded('bookingRoomTypes')),
            'guest' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'email' => $this->email,
            'mobile' => $this->mobile,
            'address' => [
                'street' => $this->street,
                'city' => $this->city,
                'post_code' => $this->post_code,
                'country' => $this->whenLoaded('country', fn() => $this->country->name),
                'state' => $this->whenLoaded('state', fn() => $this->state?->name),
            ],
            'booking_date' => $this->booking_date?->format('Y-m-d'),
            'booking_expiry_date' => $this->booking_expiry_date?->format('Y-m-d'),
            'check_in_time' => $this->check_in_time ?? '15:00:00',
            'check_out_time' => $this->check_out_time ?? '11:00:00',
            'nights' => $this->nights,
            'booking_status' => $this->booking_status,
            'payment_status' => $this->payment_status,
            'payment_gateway' => $this->payment_gateway,
            'transaction_id' => $this->transaction_id,
            'amount' => (float) $this->amount,
            'notes' => $this->notes,
            'cancellation_policy' => $this->whenLoaded('cancellationPolicy', fn() => new CancellationPolicyResource($this->cancellationPolicy)),
            'refund' => [
                'status' => $this->refund_status,
                'amount' => $this->refund_amount ? (float) $this->refund_amount : null,
                'transaction_id' => $this->refund_transaction_id,
                'processed_at' => $this->refund_processed_at?->toISOString(),
            ],
            'cancellation' => [
                'cancelled_at' => $this->cancelled_at?->toISOString(),
                'reason' => $this->cancellation_reason,
            ],
            'check_in_out' => [
                'checked_in_at' => $this->checked_in_at?->toISOString(),
                'checked_out_at' => $this->checked_out_at?->toISOString(),
                'can_check_in' => $this->canCheckIn(),
                'can_check_out' => $this->canCheckOut(),
                'is_overdue' => $this->isCheckOutOverdue(),
            ],
            'payment_log' => $this->whenLoaded('bookingPaymentLog', fn() => [
                'total_amount' => (float) $this->bookingPaymentLog->total_amount,
                'tax_amount' => (float) $this->bookingPaymentLog->tax_amount,
                'subtotal' => (float) $this->bookingPaymentLog->subtotal,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

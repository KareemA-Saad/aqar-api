<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderResource',
    title: 'Order Resource',
    description: 'Order resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_number', type: 'string', example: 'ORD-00000001'),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'status_label', type: 'string', example: 'Pending'),
        new OA\Property(property: 'payment_status', type: 'string', example: 'pending'),
        new OA\Property(property: 'payment_status_label', type: 'string', example: 'Pending'),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe', nullable: true),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'txn_123456', nullable: true),
        new OA\Property(property: 'customer', type: 'object', properties: [
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
            new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
            new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
        ]),
        new OA\Property(property: 'shipping_address', type: 'object', properties: [
            new OA\Property(property: 'address', type: 'string', example: '123 Main St'),
            new OA\Property(property: 'city', type: 'string', example: 'New York'),
            new OA\Property(property: 'state', type: 'string', example: 'NY'),
            new OA\Property(property: 'country', type: 'string', example: 'USA'),
            new OA\Property(property: 'zipcode', type: 'string', example: '10001'),
        ]),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'product_id', type: 'integer'),
            new OA\Property(property: 'product_name', type: 'string'),
            new OA\Property(property: 'variant', type: 'string', nullable: true),
            new OA\Property(property: 'quantity', type: 'integer'),
            new OA\Property(property: 'unit_price', type: 'number'),
            new OA\Property(property: 'total_price', type: 'number'),
        ])),
        new OA\Property(property: 'coupon', type: 'string', nullable: true),
        new OA\Property(property: 'coupon_discount', type: 'number', example: 10.00),
        new OA\Property(property: 'shipping_cost', type: 'number', example: 5.00),
        new OA\Property(property: 'tax_amount', type: 'number', example: 8.50),
        new OA\Property(property: 'subtotal', type: 'number', example: 100.00),
        new OA\Property(property: 'total_amount', type: 'number', example: 103.50),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->payment_track ?? 'ORD-' . str_pad($this->id, 8, '0', STR_PAD_LEFT),
            'user_id' => $this->user_id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->getPaymentStatusLabel(),
            'payment_gateway' => $this->payment_gateway,
            'transaction_id' => $this->transaction_id,

            // Customer info
            'customer' => [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
            ],

            // Shipping address
            'shipping_address' => [
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->when($this->relationLoaded('getState'), fn() => $this->getState?->name, $this->state),
                'country' => $this->when($this->relationLoaded('getCountry'), fn() => $this->getCountry?->name, $this->country),
                'zipcode' => $this->zipcode,
            ],

            // Order items
            'items' => $this->when($this->relationLoaded('sale_details'), function () {
                return $this->sale_details->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name ?? $item->product?->name,
                        'variant' => $item->variant,
                        'quantity' => $item->quantity,
                        'unit_price' => round($item->unit_price ?? 0, 2),
                        'total_price' => round($item->total_price ?? 0, 2),
                    ];
                });
            }),

            // Pricing
            'coupon' => $this->coupon,
            'coupon_discount' => round($this->coupon_discounted ?? 0, 2),
            'shipping_cost' => round($this->shipping_cost ?? 0, 2),
            'tax_amount' => round($this->tax_amount ?? 0, 2),
            'subtotal' => round($this->subtotal ?? 0, 2),
            'total_amount' => round($this->total_amount, 2),

            // Shipping option
            'selected_shipping_option' => $this->selected_shipping_option,
            'shipping_info' => $this->when($this->relationLoaded('shipping'), function () {
                return $this->shipping ? [
                    'id' => $this->shipping->id,
                    'name' => $this->shipping->name,
                    'address' => $this->shipping->address,
                    'city' => $this->shipping->city,
                    'phone' => $this->shipping->phone,
                ] : null;
            }),

            // Notes
            'message' => $this->message,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('Pending'),
            'in_progress', 'processing' => __('In Progress'),
            'shipped' => __('Shipped'),
            'delivered' => __('Delivered'),
            'complete', 'completed' => __('Complete'),
            'cancel', 'cancelled' => __('Cancelled'),
            'refunded' => __('Refunded'),
            default => ucfirst($this->status ?? 'pending'),
        };
    }

    /**
     * Get payment status label
     */
    protected function getPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'pending' => __('Pending'),
            'paid', 'complete' => __('Paid'),
            'failed' => __('Failed'),
            'refunded' => __('Refunded'),
            default => ucfirst($this->payment_status ?? 'pending'),
        };
    }
}

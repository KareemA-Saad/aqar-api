<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreCancellationPolicyRequest',
    title: 'Store Cancellation Policy Request',
    description: 'Request to create a cancellation policy',
    required: ['name', 'is_refundable'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Flexible Cancellation'),
        new OA\Property(property: 'description', type: 'string', example: 'Free cancellation up to 24 hours before check-in'),
        new OA\Property(property: 'hotel_id', type: 'integer', example: 1),
        new OA\Property(property: 'room_type_id', type: 'integer', example: null, nullable: true),
        new OA\Property(property: 'is_refundable', type: 'boolean', example: true),
        new OA\Property(property: 'is_default', type: 'boolean', example: false),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(
            property: 'tiers',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'hours_before_checkin', type: 'integer', example: 24),
                    new OA\Property(property: 'refund_percentage', type: 'integer', example: 100),
                ],
                type: 'object'
            )
        ),
    ]
)]
class StoreCancellationPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'hotel_id' => ['nullable', 'integer', 'exists:hotels,id'],
            'room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
            'is_refundable' => ['required', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'tiers' => ['nullable', 'array'],
            'tiers.*.hours_before_checkin' => ['required_with:tiers', 'integer', 'min:0'],
            'tiers.*.refund_percentage' => ['required_with:tiers', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Policy name is required.',
            'is_refundable.required' => 'Please specify if this policy is refundable.',
            'tiers.*.hours_before_checkin.required_with' => 'Hours before check-in is required for each tier.',
            'tiers.*.refund_percentage.required_with' => 'Refund percentage is required for each tier.',
            'tiers.*.refund_percentage.max' => 'Refund percentage cannot exceed 100%.',
        ];
    }
}

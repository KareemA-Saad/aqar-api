<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProcessRefundRequest',
    title: 'Process Refund Request',
    description: 'Request to process a refund for a cancelled booking',
    properties: [
        new OA\Property(property: 'amount', type: 'number', example: 299.99, description: 'Custom refund amount. If not provided, uses policy calculation.'),
        new OA\Property(property: 'reason', type: 'string', example: 'Customer requested refund'),
    ]
)]
class ProcessRefundRequest extends FormRequest
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
            'amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Refund amount cannot be negative.',
        ];
    }
}

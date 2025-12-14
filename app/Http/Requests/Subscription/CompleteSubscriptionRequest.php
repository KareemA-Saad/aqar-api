<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Complete Subscription Request DTO
 *
 * Validates data for completing a subscription payment.
 */
#[OA\Schema(
    schema: 'CompleteSubscriptionRequest',
    title: 'Complete Subscription Request',
    required: ['payment_log_id', 'transaction_id'],
    properties: [
        new OA\Property(property: 'payment_log_id', type: 'integer', example: 1, description: 'ID of the payment log'),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'txn_abc123', description: 'Transaction ID from payment gateway'),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe', nullable: true),
        new OA\Property(
            property: 'payment_data',
            type: 'object',
            nullable: true,
            description: 'Additional payment data from gateway'
        ),
    ]
)]
final class CompleteSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_log_id' => ['required', 'integer', 'exists:payment_logs,id'],
            'transaction_id' => ['required', 'string', 'max:255'],
            'payment_gateway' => ['nullable', 'string', 'max:50'],
            'payment_data' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_log_id.required' => 'Payment log ID is required.',
            'payment_log_id.exists' => 'Payment log not found.',
            'transaction_id.required' => 'Transaction ID is required.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'payment_log_id' => (int) $data['payment_log_id'],
            'transaction_id' => $data['transaction_id'],
            'payment_gateway' => $data['payment_gateway'] ?? null,
            'payment_data' => $data['payment_data'] ?? [],
        ];
    }
}

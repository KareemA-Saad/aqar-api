<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Cancel Subscription Request DTO
 *
 * Validates data for canceling a subscription.
 */
#[OA\Schema(
    schema: 'CancelSubscriptionRequest',
    title: 'Cancel Subscription Request',
    required: ['reason'],
    properties: [
        new OA\Property(
            property: 'reason',
            type: 'string',
            example: 'No longer need the service',
            maxLength: 1000,
            description: 'Reason for cancellation'
        ),
        new OA\Property(
            property: 'feedback',
            type: 'string',
            nullable: true,
            maxLength: 2000,
            description: 'Additional feedback'
        ),
    ]
)]
final class CancelSubscriptionRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:2000'],
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
            'reason.required' => 'A reason for cancellation is required.',
            'reason.max' => 'Reason must not exceed 1000 characters.',
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
            'reason' => $data['reason'],
            'feedback' => $data['feedback'] ?? null,
        ];
    }
}

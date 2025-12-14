<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Initiate Subscription Request DTO
 *
 * Validates data for initiating a new subscription.
 */
#[OA\Schema(
    schema: 'InitiateSubscriptionRequest',
    title: 'Initiate Subscription Request',
    required: ['plan_id', 'subdomain'],
    properties: [
        new OA\Property(property: 'plan_id', type: 'integer', example: 1, description: 'ID of the price plan'),
        new OA\Property(property: 'subdomain', type: 'string', example: 'mystore', maxLength: 63, description: 'Subdomain for tenant'),
        new OA\Property(property: 'theme', type: 'string', example: 'default', nullable: true, description: 'Theme slug'),
        new OA\Property(property: 'coupon_code', type: 'string', example: 'SAVE20', nullable: true, description: 'Coupon code for discount'),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe', nullable: true, description: 'Payment gateway identifier'),
        new OA\Property(property: 'is_trial', type: 'boolean', example: false, nullable: true, description: 'Start as trial subscription'),
    ]
)]
final class InitiateSubscriptionRequest extends FormRequest
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
            'plan_id' => ['required', 'integer', 'exists:price_plans,id'],
            'subdomain' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/',
                'unique:tenants,id',
            ],
            'theme' => ['nullable', 'string', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'payment_gateway' => ['nullable', 'string', 'max:50'],
            'is_trial' => ['nullable', 'boolean'],
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
            'plan_id.required' => 'Plan selection is required.',
            'plan_id.exists' => 'Selected plan does not exist.',
            'subdomain.required' => 'Subdomain is required.',
            'subdomain.regex' => 'Subdomain must contain only lowercase letters, numbers, and hyphens.',
            'subdomain.unique' => 'This subdomain is already taken.',
            'coupon_code.max' => 'Coupon code is too long.',
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
            'plan_id' => (int) $data['plan_id'],
            'subdomain' => strtolower(trim($data['subdomain'])),
            'theme' => $data['theme'] ?? null,
            'coupon_code' => $data['coupon_code'] ?? null,
            'payment_gateway' => $data['payment_gateway'] ?? null,
            'is_trial' => (bool) ($data['is_trial'] ?? false),
        ];
    }
}

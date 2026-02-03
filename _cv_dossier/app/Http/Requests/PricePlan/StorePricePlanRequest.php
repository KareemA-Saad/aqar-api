<?php

declare(strict_types=1);

namespace App\Http\Requests\PricePlan;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Store Price Plan Request DTO
 *
 * Validates data for creating a new price plan.
 */
#[OA\Schema(
    schema: 'StorePricePlanRequest',
    title: 'Store Price Plan Request',
    required: ['title', 'price', 'type', 'status'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Premium Plan', maxLength: 191),
        new OA\Property(property: 'subtitle', type: 'string', example: 'Best for growing businesses', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99, minimum: 0),
        new OA\Property(
            property: 'type',
            type: 'integer',
            description: '0 = Monthly, 1 = Yearly, 2 = Lifetime',
            enum: [0, 1, 2],
            example: 0
        ),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'zero_price', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'has_trial', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'trial_days', type: 'integer', example: 14, minimum: 0, nullable: true),
        new OA\Property(property: 'page_permission_feature', type: 'integer', example: 10, nullable: true, description: 'Max pages allowed'),
        new OA\Property(property: 'blog_permission_feature', type: 'integer', example: 50, nullable: true, description: 'Max blogs allowed'),
        new OA\Property(property: 'product_permission_feature', type: 'integer', example: 100, nullable: true, description: 'Max products allowed'),
        new OA\Property(property: 'portfolio_permission_feature', type: 'integer', example: 20, nullable: true, description: 'Max portfolio items'),
        new OA\Property(property: 'storage_permission_feature', type: 'integer', example: 1024, nullable: true, description: 'Storage limit in MB'),
        new OA\Property(property: 'appointment_permission_feature', type: 'integer', example: 100, nullable: true, description: 'Max appointments'),
        new OA\Property(
            property: 'features',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'feature_name', type: 'string', example: 'eCommerce'),
                    new OA\Property(property: 'status', type: 'boolean', example: true),
                ],
                type: 'object'
            ),
            nullable: true,
            description: 'Array of plan features'
        ),
        new OA\Property(
            property: 'faq',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'answer', type: 'string'),
                ],
                type: 'object'
            ),
            nullable: true
        ),
    ]
)]
final class StorePricePlanRequest extends FormRequest
{
    /**
     * Plan type constants.
     */
    public const TYPE_MONTHLY = 0;
    public const TYPE_YEARLY = 1;
    public const TYPE_LIFETIME = 2;

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
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'integer', 'in:0,1,2'],
            'status' => ['required', 'boolean'],
            'zero_price' => ['nullable', 'boolean'],
            'has_trial' => ['nullable', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'page_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'blog_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'product_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'portfolio_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'storage_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'appointment_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'features' => ['nullable', 'array'],
            'features.*.feature_name' => ['required_with:features', 'string', 'max:191'],
            'features.*.status' => ['required_with:features', 'boolean'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['required_with:faq', 'string'],
            'faq.*.answer' => ['required_with:faq', 'string'],
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
            'title.required' => 'Plan title is required.',
            'price.required' => 'Plan price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'type.required' => 'Plan type is required.',
            'type.in' => 'Plan type must be 0 (Monthly), 1 (Yearly), or 2 (Lifetime).',
            'status.required' => 'Plan status is required.',
            'features.*.feature_name.required_with' => 'Feature name is required.',
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
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'price' => (float) $data['price'],
            'type' => (int) $data['type'],
            'status' => (bool) $data['status'],
            'zero_price' => (bool) ($data['zero_price'] ?? false),
            'has_trial' => (bool) ($data['has_trial'] ?? false),
            'trial_days' => $data['has_trial'] ?? false ? (int) ($data['trial_days'] ?? 0) : 0,
            'page_permission_feature' => isset($data['page_permission_feature']) ? (int) $data['page_permission_feature'] : null,
            'blog_permission_feature' => isset($data['blog_permission_feature']) ? (int) $data['blog_permission_feature'] : null,
            'product_permission_feature' => isset($data['product_permission_feature']) ? (int) $data['product_permission_feature'] : null,
            'portfolio_permission_feature' => isset($data['portfolio_permission_feature']) ? (int) $data['portfolio_permission_feature'] : null,
            'storage_permission_feature' => isset($data['storage_permission_feature']) ? (int) $data['storage_permission_feature'] : null,
            'appointment_permission_feature' => isset($data['appointment_permission_feature']) ? (int) $data['appointment_permission_feature'] : null,
            'features' => $data['features'] ?? [],
            'faq' => isset($data['faq']) ? serialize($data['faq']) : null,
        ];
    }
}

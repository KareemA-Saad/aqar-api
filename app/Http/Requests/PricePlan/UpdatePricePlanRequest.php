<?php

declare(strict_types=1);

namespace App\Http\Requests\PricePlan;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Update Price Plan Request DTO
 *
 * Validates data for updating an existing price plan.
 */
#[OA\Schema(
    schema: 'UpdatePricePlanRequest',
    title: 'Update Price Plan Request',
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
        new OA\Property(property: 'page_permission_feature', type: 'integer', example: 10, nullable: true),
        new OA\Property(property: 'blog_permission_feature', type: 'integer', example: 50, nullable: true),
        new OA\Property(property: 'product_permission_feature', type: 'integer', example: 100, nullable: true),
        new OA\Property(property: 'portfolio_permission_feature', type: 'integer', example: 20, nullable: true),
        new OA\Property(property: 'storage_permission_feature', type: 'integer', example: 1024, nullable: true),
        new OA\Property(property: 'appointment_permission_feature', type: 'integer', example: 100, nullable: true),
        new OA\Property(
            property: 'features',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', nullable: true, description: 'Feature ID for update, null for new'),
                    new OA\Property(property: 'feature_name', type: 'string', example: 'eCommerce'),
                    new OA\Property(property: 'status', type: 'boolean', example: true),
                ],
                type: 'object'
            ),
            nullable: true
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
final class UpdatePricePlanRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'type' => ['sometimes', 'required', 'integer', 'in:0,1,2'],
            'status' => ['sometimes', 'required', 'boolean'],
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
            'features.*.id' => ['nullable', 'integer', 'exists:plan_features,id'],
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
            'price.numeric' => 'Price must be a valid number.',
            'type.in' => 'Plan type must be 0 (Monthly), 1 (Yearly), or 2 (Lifetime).',
            'features.*.id.exists' => 'Feature ID does not exist.',
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
        $result = [];

        if (isset($data['title'])) {
            $result['title'] = $data['title'];
        }
        if (array_key_exists('subtitle', $data)) {
            $result['subtitle'] = $data['subtitle'];
        }
        if (isset($data['price'])) {
            $result['price'] = (float) $data['price'];
        }
        if (isset($data['type'])) {
            $result['type'] = (int) $data['type'];
        }
        if (isset($data['status'])) {
            $result['status'] = (bool) $data['status'];
        }
        if (array_key_exists('zero_price', $data)) {
            $result['zero_price'] = (bool) ($data['zero_price'] ?? false);
        }
        if (array_key_exists('has_trial', $data)) {
            $result['has_trial'] = (bool) ($data['has_trial'] ?? false);
            $result['trial_days'] = $result['has_trial'] ? (int) ($data['trial_days'] ?? 0) : 0;
        }

        $permissionFields = [
            'page_permission_feature',
            'blog_permission_feature',
            'product_permission_feature',
            'portfolio_permission_feature',
            'storage_permission_feature',
            'appointment_permission_feature',
        ];

        foreach ($permissionFields as $field) {
            if (array_key_exists($field, $data)) {
                $result[$field] = $data[$field] !== null ? (int) $data[$field] : null;
            }
        }

        if (array_key_exists('features', $data)) {
            $result['features'] = $data['features'] ?? [];
        }

        if (array_key_exists('faq', $data)) {
            $result['faq'] = isset($data['faq']) ? serialize($data['faq']) : null;
        }

        return $result;
    }
}

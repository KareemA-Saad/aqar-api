<?php

declare(strict_types=1);

namespace App\Http\Requests\PricePlan;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Reorder Features Request DTO
 *
 * Validates data for reordering plan features.
 */
#[OA\Schema(
    schema: 'ReorderFeaturesRequest',
    title: 'Reorder Features Request',
    required: ['feature_ids'],
    properties: [
        new OA\Property(
            property: 'feature_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [3, 1, 2, 5, 4],
            description: 'Array of feature IDs in desired order'
        ),
    ]
)]
final class ReorderFeaturesRequest extends FormRequest
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
            'feature_ids' => ['required', 'array', 'min:1'],
            'feature_ids.*' => ['required', 'integer', 'exists:plan_features,id'],
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
            'feature_ids.required' => 'Feature IDs are required.',
            'feature_ids.array' => 'Feature IDs must be an array.',
            'feature_ids.*.exists' => 'One or more feature IDs do not exist.',
        ];
    }

    /**
     * Get validated feature IDs.
     *
     * @return array<int, int>
     */
    public function getFeatureIds(): array
    {
        return array_map('intval', $this->validated('feature_ids'));
    }
}

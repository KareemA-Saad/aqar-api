<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Update Category Request
 */
#[OA\Schema(
    schema: 'UpdateCategoryRequest',
    properties: [
        new OA\Property(property: 'title', type: 'object', nullable: true),
        new OA\Property(property: 'description', type: 'object', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1], nullable: true),
    ]
)]
final class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'array'],
            'title.en' => ['required_with:title', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'image' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}

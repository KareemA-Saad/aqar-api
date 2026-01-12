<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Store Category Request
 */
#[OA\Schema(
    schema: 'StoreCategoryRequest',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'object', example: ['en' => 'Medical', 'ar' => 'طبي']),
        new OA\Property(property: 'description', type: 'object', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1], default: 1),
    ]
)]
final class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'image' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}

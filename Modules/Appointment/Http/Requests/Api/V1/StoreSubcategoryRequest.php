<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Store Subcategory Request
 */
#[OA\Schema(
    schema: 'StoreSubcategoryRequest',
    required: ['title', 'category_id'],
    properties: [
        new OA\Property(property: 'title', type: 'object', example: ['en' => 'Dental Care', 'ar' => 'العناية بالأسنان']),
        new OA\Property(property: 'description', type: 'object', nullable: true),
        new OA\Property(property: 'category_id', type: 'integer'),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1], default: 1),
    ]
)]
final class StoreSubcategoryRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:appointment_categories,id'],
            'image' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}

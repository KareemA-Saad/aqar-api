<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'KnowledgebaseCategoryRequest',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Getting Started'),
        new OA\Property(property: 'image', type: 'string', example: 'categories/getting-started.jpg', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
    ]
)]
class KnowledgebaseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}

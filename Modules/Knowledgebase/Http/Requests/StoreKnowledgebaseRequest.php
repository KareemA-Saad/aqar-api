<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreKnowledgebaseRequest',
    required: ['title', 'description'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'How to use the platform'),
        new OA\Property(property: 'slug', type: 'string', example: 'how-to-use-the-platform'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'category_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'knowledgebase/article.jpg', nullable: true),
        new OA\Property(property: 'files', type: 'string', example: 'file1.pdf,file2.pdf', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'meta_title', type: 'string', nullable: true),
        new OA\Property(property: 'meta_description', type: 'string', nullable: true),
        new OA\Property(property: 'meta_tags', type: 'string', nullable: true),
    ]
)]
class StoreKnowledgebaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:knowledgebases,slug'],
            'description' => ['required', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:knowledgebase_categories,id'],
            'image' => ['nullable', 'string', 'max:255'],
            'files' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_tags' => ['nullable', 'string'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Portfolio\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StorePortfolioRequest',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Modern Website Design'),
        new OA\Property(property: 'slug', type: 'string', example: 'modern-website-design'),
        new OA\Property(property: 'url', type: 'string', example: 'https://example.com', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'category_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'portfolio/featured.jpg', nullable: true),
        new OA\Property(property: 'image_gallery', type: 'string', example: 'img1.jpg,img2.jpg', nullable: true),
        new OA\Property(property: 'client', type: 'string', example: 'Acme Corp', nullable: true),
        new OA\Property(property: 'design', type: 'string', example: 'Minimalist', nullable: true),
        new OA\Property(property: 'typography', type: 'string', example: 'Roboto', nullable: true),
        new OA\Property(property: 'tags', type: 'string', example: 'web,design,modern', nullable: true),
        new OA\Property(property: 'file', type: 'string', nullable: true),
        new OA\Property(property: 'download', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'meta_title', type: 'string', nullable: true),
        new OA\Property(property: 'meta_description', type: 'string', nullable: true),
        new OA\Property(property: 'meta_tags', type: 'string', nullable: true),
    ]
)]
class StorePortfolioRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:portfolios,slug'],
            'url' => ['nullable', 'string', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:portfolio_categories,id'],
            'image' => ['nullable', 'string', 'max:255'],
            'image_gallery' => ['nullable', 'string'],
            'client' => ['nullable', 'string', 'max:255'],
            'design' => ['nullable', 'string', 'max:255'],
            'typography' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'file' => ['nullable', 'string', 'max:255'],
            'download' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_tags' => ['nullable', 'string'],
        ];
    }
}

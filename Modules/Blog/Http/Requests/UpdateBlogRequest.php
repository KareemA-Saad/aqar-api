<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Form Request for updating an existing blog post.
 */
#[OA\Schema(
    schema: 'UpdateBlogRequest',
    title: 'Update Blog Request',
    description: 'Request body for updating an existing blog post',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Getting Started with Laravel', minLength: 2, maxLength: 500),
        new OA\Property(property: 'slug', type: 'string', example: 'getting-started-with-laravel', nullable: true, maxLength: 500),
        new OA\Property(property: 'blog_content', type: 'string', example: 'Full blog content here...'),
        new OA\Property(property: 'excerpt', type: 'string', example: 'A quick introduction', nullable: true, maxLength: 500),
        new OA\Property(property: 'category_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'image', type: 'string', example: '1', nullable: true),
        new OA\Property(property: 'image_gallery', type: 'string', example: '1,2,3', nullable: true),
        new OA\Property(property: 'video_url', type: 'string', example: 'https://youtube.com/watch?v=xxx', nullable: true),
        new OA\Property(property: 'tags', type: 'string', example: 'laravel,php,web', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'featured', type: 'boolean', example: false),
        new OA\Property(property: 'visibility', type: 'string', example: 'public', nullable: true),
        new OA\Property(property: 'meta_title', type: 'string', example: 'SEO Title', nullable: true),
        new OA\Property(property: 'meta_description', type: 'string', example: 'SEO Description', nullable: true),
        new OA\Property(property: 'meta_keywords', type: 'string', example: 'seo,keywords', nullable: true),
    ]
)]
final class UpdateBlogRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $blogId = $this->route('id');

        return [
            'title' => ['sometimes', 'required', 'string', 'min:2', 'max:500'],
            'slug' => [
                'nullable',
                'string',
                'max:500',
                Rule::unique('blogs', 'slug')->ignore($blogId),
            ],
            'blog_content' => ['sometimes', 'required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'image' => ['nullable', 'string', 'max:191'],
            'image_gallery' => ['nullable', 'string', 'max:500'],
            'video_url' => ['nullable', 'string', 'max:500', 'url'],
            'tags' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'visibility' => ['nullable', 'string', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
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
            'title.required' => 'The blog title is required.',
            'title.min' => 'The blog title must be at least 2 characters.',
            'blog_content.required' => 'The blog content is required.',
            'slug.unique' => 'This slug is already in use.',
            'category_id.exists' => 'The selected category does not exist.',
            'video_url.url' => 'The video URL must be a valid URL.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'blog title',
            'blog_content' => 'blog content',
            'category_id' => 'category',
            'video_url' => 'video URL',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'meta_keywords' => 'meta keywords',
        ];
    }
}

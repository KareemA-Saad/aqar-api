<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Form Request for creating/updating a blog category.
 */
#[OA\Schema(
    schema: 'StoreCategoryRequest',
    title: 'Store Category Request',
    description: 'Request body for creating or updating a blog category',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Technology', minLength: 2, maxLength: 191),
        new OA\Property(property: 'status', type: 'boolean', example: true),
    ]
)]
final class StoreCategoryRequest extends FormRequest
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
        $categoryId = $this->route('id');

        return [
            'title' => [
                'required',
                'string',
                'min:2',
                'max:191',
                $categoryId
                    ? Rule::unique('blog_categories', 'title')->ignore($categoryId)
                    : 'unique:blog_categories,title',
            ],
            'status' => ['nullable', 'boolean'],
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
            'title.required' => 'The category title is required.',
            'title.min' => 'The category title must be at least 2 characters.',
            'title.unique' => 'A category with this title already exists.',
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
            'title' => 'category title',
        ];
    }
}

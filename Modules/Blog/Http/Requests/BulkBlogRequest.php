<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for bulk actions on blog posts.
 */
#[OA\Schema(
    schema: 'BulkBlogRequest',
    title: 'Bulk Blog Request',
    description: 'Request body for bulk actions on blog posts',
    required: ['ids', 'action'],
    properties: [
        new OA\Property(
            property: 'ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'action',
            type: 'string',
            enum: ['delete', 'publish', 'unpublish'],
            example: 'delete'
        ),
    ]
)]
final class BulkBlogRequest extends FormRequest
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
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:blogs,id'],
            'action' => ['required', 'string', 'in:delete,publish,unpublish'],
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
            'ids.required' => 'Please select at least one blog post.',
            'ids.array' => 'Invalid selection format.',
            'ids.min' => 'Please select at least one blog post.',
            'ids.*.exists' => 'One or more selected blog posts do not exist.',
            'action.required' => 'Please specify an action.',
            'action.in' => 'Invalid action. Allowed actions: delete, publish, unpublish.',
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
            'ids' => 'blog posts',
            'ids.*' => 'blog post',
            'action' => 'bulk action',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for creating a blog comment.
 */
#[OA\Schema(
    schema: 'StoreCommentRequest',
    title: 'Store Comment Request',
    description: 'Request body for creating a blog comment',
    required: ['comment_content'],
    properties: [
        new OA\Property(property: 'comment_content', type: 'string', example: 'Great article! Thanks for sharing.', minLength: 1, maxLength: 5000),
        new OA\Property(property: 'parent_id', type: 'integer', example: 1, nullable: true, description: 'Parent comment ID for replies'),
    ]
)]
final class StoreCommentRequest extends FormRequest
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
            'comment_content' => ['required', 'string', 'min:1', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:blog_comments,id'],
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
            'comment_content.required' => 'The comment content is required.',
            'comment_content.min' => 'The comment cannot be empty.',
            'comment_content.max' => 'The comment is too long. Maximum 5000 characters allowed.',
            'parent_id.exists' => 'The parent comment does not exist.',
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
            'comment_content' => 'comment',
            'parent_id' => 'parent comment',
        ];
    }
}

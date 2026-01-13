<?php

declare(strict_types=1);

namespace Modules\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for storing an event comment.
 */
#[OA\Schema(
    schema: 'StoreEventCommentRequest',
    title: 'Store Event Comment Request',
    description: 'Request body for creating an event comment',
    required: ['commented_by', 'comment_content'],
    properties: [
        new OA\Property(property: 'commented_by', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'comment_content', type: 'string', example: 'Great event! Looking forward to it.'),
    ]
)]
final class StoreEventCommentRequest extends FormRequest
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
            'commented_by' => ['required', 'string', 'max:191'],
            'comment_content' => ['required', 'string', 'min:2'],
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
            'commented_by.required' => 'Your name is required.',
            'comment_content.required' => 'The comment content is required.',
            'comment_content.min' => 'The comment must be at least 2 characters.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for creating/updating an event category.
 */
#[OA\Schema(
    schema: 'EventCategoryRequest',
    title: 'Event Category Request',
    description: 'Request body for creating or updating an event category',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Technology', minLength: 2, maxLength: 191),
        new OA\Property(property: 'status', type: 'boolean', example: true),
    ]
)]
final class EventCategoryRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:2', 'max:191'],
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
        ];
    }
}

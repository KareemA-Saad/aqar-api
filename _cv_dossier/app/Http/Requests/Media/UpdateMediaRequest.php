<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Update Media Request
 *
 * Validates media metadata updates.
 */
#[OA\Schema(
    schema: 'UpdateMediaRequest',
    title: 'Update Media Request',
    properties: [
        new OA\Property(
            property: 'title',
            type: 'string',
            maxLength: 255,
            description: 'Title of the media file',
            nullable: true
        ),
        new OA\Property(
            property: 'alt',
            type: 'string',
            maxLength: 255,
            description: 'Alt text for the media (used for accessibility)',
            nullable: true
        ),
    ]
)]
class UpdateMediaRequest extends FormRequest
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
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'alt' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'The title may not be greater than 255 characters.',
            'alt.max' => 'The alt text may not be greater than 255 characters.',
        ];
    }

    /**
     * Get the validated title.
     */
    public function getTitle(): ?string
    {
        return $this->input('title');
    }

    /**
     * Get the validated alt text.
     */
    public function getAltText(): ?string
    {
        return $this->input('alt');
    }
}

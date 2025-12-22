<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\MediaUploader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Bulk Delete Media Request
 *
 * Validates bulk delete operations for media files.
 */
#[OA\Schema(
    schema: 'BulkDeleteMediaRequest',
    title: 'Bulk Delete Media Request',
    required: ['ids'],
    properties: [
        new OA\Property(
            property: 'ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of media IDs to delete',
            example: [1, 2, 3]
        ),
    ]
)]
class BulkDeleteMediaRequest extends FormRequest
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
            'ids' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],
            'ids.*' => [
                'required',
                'integer',
                Rule::exists(MediaUploader::class, 'id'),
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
            'ids.required' => 'Please select at least one media file to delete.',
            'ids.array' => 'Invalid format for media IDs.',
            'ids.min' => 'Please select at least one media file to delete.',
            'ids.max' => 'You can delete a maximum of 100 files at once.',
            'ids.*.integer' => 'Invalid media ID format.',
            'ids.*.exists' => 'One or more selected media files do not exist.',
        ];
    }

    /**
     * Get the media IDs to delete.
     *
     * @return array<int>
     */
    public function getMediaIds(): array
    {
        return array_map('intval', $this->input('ids', []));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use OpenApi\Attributes as OA;

/**
 * Upload Media Request
 *
 * Validates file uploads for the media system.
 */
#[OA\Schema(
    schema: 'UploadMediaRequest',
    title: 'Upload Media Request',
    required: ['file'],
    properties: [
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            description: 'The file to upload'
        ),
        new OA\Property(
            property: 'files',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'binary'),
            description: 'Multiple files to upload (alternative to single file)'
        ),
        new OA\Property(
            property: 'alt',
            type: 'string',
            maxLength: 255,
            description: 'Alt text for the media',
            nullable: true
        ),
        new OA\Property(
            property: 'folder',
            type: 'string',
            maxLength: 100,
            description: 'Custom folder path within media storage',
            nullable: true
        ),
    ]
)]
class UploadMediaRequest extends FormRequest
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
        $allowedExtensions = implode(',', config('media.allowed_extensions', []));
        $maxSize = config('media.max_file_size', 10240);

        return [
            'file' => [
                'required_without:files',
                'nullable',
                File::types(config('media.allowed_extensions', []))
                    ->max($maxSize),
            ],
            'files' => [
                'required_without:file',
                'nullable',
                'array',
                'max:10',
            ],
            'files.*' => [
                File::types(config('media.allowed_extensions', []))
                    ->max($maxSize),
            ],
            'alt' => [
                'nullable',
                'string',
                'max:255',
            ],
            'folder' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\-_\/]+$/',
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
        $maxSize = config('media.max_file_size', 10240);
        $maxSizeMb = round($maxSize / 1024, 1);

        return [
            'file.required_without' => 'A file is required when not uploading multiple files.',
            'file.mimes' => 'The file must be a valid type (' . implode(', ', config('media.allowed_extensions', [])) . ').',
            'file.max' => "The file may not be larger than {$maxSizeMb} MB.",
            'files.required_without' => 'Files are required when not uploading a single file.',
            'files.max' => 'You can upload a maximum of 10 files at once.',
            'files.*.mimes' => 'Each file must be a valid type.',
            'files.*.max' => "Each file may not be larger than {$maxSizeMb} MB.",
            'folder.regex' => 'The folder name may only contain letters, numbers, hyphens, underscores, and forward slashes.',
        ];
    }

    /**
     * Check if this is a multiple file upload.
     */
    public function isMultipleUpload(): bool
    {
        return $this->hasFile('files') && is_array($this->file('files'));
    }

    /**
     * Get the uploaded files (single or multiple).
     *
     * @return array<\Illuminate\Http\UploadedFile>
     */
    public function getUploadedFiles(): array
    {
        if ($this->isMultipleUpload()) {
            return $this->file('files');
        }

        return $this->hasFile('file') ? [$this->file('file')] : [];
    }

    /**
     * Get the custom folder path if provided.
     */
    public function getFolder(): ?string
    {
        return $this->input('folder');
    }

    /**
     * Get the alt text if provided.
     */
    public function getAltText(): ?string
    {
        return $this->input('alt');
    }
}

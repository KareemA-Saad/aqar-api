<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($this->route('id')),
            ],
            'description' => 'nullable|string',
            'image_id' => 'nullable|exists:media_uploaders,id',
            'status_id' => 'nullable|exists:statuses,id',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Portfolio\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PortfolioCategoryRequest',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Web Design'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
    ]
)]
class PortfolioCategoryRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}

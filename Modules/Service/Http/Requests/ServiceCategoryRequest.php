<?php

declare(strict_types=1);

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ServiceCategoryRequest',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Web Development'),
        new OA\Property(property: 'icon_type', type: 'string', enum: ['class', 'image'], nullable: true, example: 'class'),
        new OA\Property(property: 'icon_class', type: 'string', nullable: true, example: 'fas fa-code'),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: 'categories/web-dev.jpg'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
    ]
)]
class ServiceCategoryRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:191'],
            'icon_type' => ['nullable', 'string', 'in:class,image'],
            'icon_class' => ['nullable', 'string', 'max:191'],
            'image' => ['nullable', 'string', 'max:191'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreServiceRequest',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Web Development'),
        new OA\Property(property: 'slug', type: 'string', example: 'web-development'),
        new OA\Property(property: 'description', type: 'string', example: 'Professional web development services'),
        new OA\Property(property: 'category_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'price_plan', type: 'string', example: '$50/hour', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'services/web-dev.jpg', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'meta_tag', type: 'string', nullable: true),
        new OA\Property(property: 'meta_description', type: 'string', nullable: true),
        new OA\Property(property: 'meta_title', type: 'string', nullable: true),
        new OA\Property(property: 'meta_tags', type: 'string', nullable: true),
    ]
)]
class StoreServiceRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', 'unique:services,slug'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'price_plan' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'meta_tag' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_tags' => ['nullable', 'string'],
        ];
    }
}

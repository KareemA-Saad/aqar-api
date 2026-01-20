<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateAreaRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'type', type: 'string', enum: ['super_area', 'area', 'sub_area']),
        new OA\Property(property: 'status', type: 'boolean'),
    ]
)]
class UpdateAreaRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $areaId = $this->route('area');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:re_areas,id', "not_in:{$areaId}"],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('re_areas', 'slug')->ignore($areaId)],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', Rule::in(['super_area', 'area', 'sub_area'])],
            'order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
            
            // SEO
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'parent_id.not_in' => 'An area cannot be its own parent.',
        ];
    }
}

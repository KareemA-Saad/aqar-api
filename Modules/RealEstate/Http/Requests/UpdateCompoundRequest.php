<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateCompoundRequest',
    properties: [
        new OA\Property(property: 'area_id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'construction_status', type: 'string', enum: ['planning', 'under_construction', 'completed']),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'is_published', type: 'boolean'),
    ]
)]
class UpdateCompoundRequest extends FormRequest
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
        $compoundId = $this->route('compound');

        return [
            'area_id' => ['sometimes', 'integer', 'exists:re_areas,id'],
            'developer_id' => ['nullable', 'integer', 'exists:re_developers,id'],
            
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('re_compounds', 'slug')->ignore($compoundId)],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:500'],
            
            // Location
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            // Media
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'virtual_tour_url' => ['nullable', 'url', 'max:500'],
            
            // Details
            'launch_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'delivery_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'total_area' => ['nullable', 'numeric', 'min:0'],
            'units_count' => ['nullable', 'integer', 'min:0'],
            
            // Status
            'construction_status' => ['nullable', 'string', Rule::in(['planning', 'under_construction', 'completed'])],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
            
            // SEO
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            
            // Amenities
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:re_amenities,id'],
        ];
    }
}

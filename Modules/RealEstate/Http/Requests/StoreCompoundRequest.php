<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreCompoundRequest',
    required: ['area_id', 'title'],
    properties: [
        new OA\Property(property: 'area_id', type: 'integer', example: 1),
        new OA\Property(property: 'developer_id', type: 'integer', nullable: true),
        new OA\Property(property: 'title', type: 'string', example: 'Tierra Compound'),
        new OA\Property(property: 'slug', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'latitude', type: 'number', nullable: true),
        new OA\Property(property: 'longitude', type: 'number', nullable: true),
        new OA\Property(property: 'construction_status', type: 'string', enum: ['planning', 'under_construction', 'completed']),
        new OA\Property(property: 'amenity_ids', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'is_published', type: 'boolean'),
    ]
)]
class StoreCompoundRequest extends FormRequest
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
        return [
            'area_id' => ['required', 'integer', 'exists:re_areas,id'],
            'developer_id' => ['nullable', 'integer', 'exists:re_developers,id'],
            
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:re_compounds,slug'],
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

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'area_id.required' => 'Please select an area for this compound.',
            'area_id.exists' => 'The selected area does not exist.',
            'title.required' => 'Compound title is required.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }
}

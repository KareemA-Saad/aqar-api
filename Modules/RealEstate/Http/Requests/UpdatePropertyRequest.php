<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RE_UpdatePropertyRequest',
    properties: [
        new OA\Property(property: 'compound_id', type: 'integer'),
        new OA\Property(property: 'property_type_id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'price', type: 'number'),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'is_published', type: 'boolean'),
    ]
)]
class UpdatePropertyRequest extends FormRequest
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
        $propertyId = $this->route('property');

        return [
            'compound_id' => ['sometimes', 'integer', 'exists:re_compounds,id'],
            'property_type_id' => ['sometimes', 'integer', 'exists:re_property_types,id'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('re_properties', 'slug')->ignore($propertyId)],
            'description' => ['nullable', 'string'],
            
            // Pricing
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(['USD', 'EUR', 'EGP', 'SAR', 'AED'])],
            'price_type' => ['nullable', 'string', Rule::in(['total', 'per_sqm'])],
            'listing_type' => ['nullable', 'string', Rule::in(['sale', 'rent'])],
            'payment_option' => ['nullable', 'string', Rule::in(['cash', 'installment', 'both'])],
            'installment_details' => ['nullable', 'array'],
            
            // Features
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', Rule::in(['sqm', 'sqft'])],
            'floor_number' => ['nullable', 'integer', 'min:0'],
            'total_floors' => ['nullable', 'integer', 'min:0'],
            'finishing' => ['nullable', 'string', Rule::in(['finished', 'semi_finished', 'unfinished', 'furnished'])],
            'view' => ['nullable', 'string', Rule::in(['garden', 'pool', 'street', 'sea', 'city', 'landscape', 'none'])],
            
            // Availability
            'is_available' => ['nullable', 'boolean'],
            'delivery_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:50', Rule::unique('re_properties', 'reference_number')->ignore($propertyId)],
            
            // Media
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'virtual_tour_url' => ['nullable', 'url', 'max:500'],
            'floor_plan_images' => ['nullable', 'array'],
            
            // SEO
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            
            // Status
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
            
            // Amenities
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:re_amenities,id'],
        ];
    }
}

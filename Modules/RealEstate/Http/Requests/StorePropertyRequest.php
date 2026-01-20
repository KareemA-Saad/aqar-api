<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RE_StorePropertyRequest',
    required: ['compound_id', 'property_type_id', 'title', 'price'],
    properties: [
        new OA\Property(property: 'compound_id', type: 'integer', example: 1),
        new OA\Property(property: 'property_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'agent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'title', type: 'string', example: 'Modern Apartment in Tierra'),
        new OA\Property(property: 'slug', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', example: 500000),
        new OA\Property(property: 'currency', type: 'string', example: 'USD'),
        new OA\Property(property: 'price_type', type: 'string', enum: ['total', 'per_sqm']),
        new OA\Property(property: 'listing_type', type: 'string', enum: ['sale', 'rent']),
        new OA\Property(property: 'payment_option', type: 'string', enum: ['cash', 'installment', 'both']),
        new OA\Property(property: 'bedrooms', type: 'integer', nullable: true),
        new OA\Property(property: 'bathrooms', type: 'integer', nullable: true),
        new OA\Property(property: 'area', type: 'number', nullable: true),
        new OA\Property(property: 'area_unit', type: 'string', enum: ['sqm', 'sqft']),
        new OA\Property(property: 'floor_number', type: 'integer', nullable: true),
        new OA\Property(property: 'finishing', type: 'string', enum: ['finished', 'semi_finished', 'unfinished', 'furnished']),
        new OA\Property(property: 'amenity_ids', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'is_published', type: 'boolean'),
    ]
)]
class StorePropertyRequest extends FormRequest
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
            'compound_id' => ['required', 'integer', 'exists:re_compounds,id'],
            'property_type_id' => ['required', 'integer', 'exists:re_property_types,id'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:re_properties,slug'],
            'description' => ['nullable', 'string'],
            
            // Pricing
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(['USD', 'EUR', 'EGP', 'SAR', 'AED'])],
            'price_type' => ['nullable', 'string', Rule::in(['total', 'per_sqm'])],
            'listing_type' => ['nullable', 'string', Rule::in(['sale', 'rent'])],
            'payment_option' => ['nullable', 'string', Rule::in(['cash', 'installment', 'both'])],
            'installment_details' => ['nullable', 'array'],
            'installment_details.down_payment' => ['nullable', 'numeric', 'min:0'],
            'installment_details.monthly_payment' => ['nullable', 'numeric', 'min:0'],
            'installment_details.years' => ['nullable', 'integer', 'min:1', 'max:30'],
            
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
            'reference_number' => ['nullable', 'string', 'max:50', 'unique:re_properties,reference_number'],
            
            // Media
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'virtual_tour_url' => ['nullable', 'url', 'max:500'],
            'floor_plan_images' => ['nullable', 'array'],
            'floor_plan_images.*' => ['string', 'max:255'],
            
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

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'compound_id.required' => 'Please select a compound for this property.',
            'compound_id.exists' => 'The selected compound does not exist.',
            'property_type_id.required' => 'Please select a property type.',
            'property_type_id.exists' => 'The selected property type does not exist.',
            'title.required' => 'Property title is required.',
            'price.required' => 'Property price is required.',
            'price.min' => 'Price must be a positive number.',
        ];
    }
}

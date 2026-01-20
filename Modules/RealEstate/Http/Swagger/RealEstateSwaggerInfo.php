<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Swagger;

use OpenApi\Attributes as OA;

/**
 * RealEstate Module OpenAPI Documentation
 * 
 * This file contains the OpenAPI schema definitions and documentation
 * for the RealEstate module API endpoints.
 */

// ============================================
// API Tags Definition
// ============================================

#[OA\Tag(
    name: 'Properties',
    description: 'Public property listing and detail endpoints'
)]
#[OA\Tag(
    name: 'Compounds',
    description: 'Public compound/project listing and detail endpoints'
)]
#[OA\Tag(
    name: 'Areas',
    description: 'Location/area hierarchy and listing endpoints'
)]
#[OA\Tag(
    name: 'Developers',
    description: 'Developer/builder listing endpoints'
)]
#[OA\Tag(
    name: 'Search',
    description: 'Advanced search and autocomplete endpoints'
)]
#[OA\Tag(
    name: 'Saved Properties',
    description: 'User saved/favorite properties endpoints'
)]
#[OA\Tag(
    name: 'Inquiries',
    description: 'Property inquiry submission endpoints'
)]
#[OA\Tag(
    name: 'Admin - Properties',
    description: 'Admin property management endpoints'
)]
#[OA\Tag(
    name: 'Admin - Compounds',
    description: 'Admin compound management endpoints'
)]
#[OA\Tag(
    name: 'Admin - Areas',
    description: 'Admin area/location management endpoints'
)]
#[OA\Tag(
    name: 'Admin - Developers',
    description: 'Admin developer management endpoints'
)]
#[OA\Tag(
    name: 'Admin - Property Types',
    description: 'Admin property type management endpoints'
)]
#[OA\Tag(
    name: 'Admin - Amenities',
    description: 'Admin amenity management endpoints'
)]
#[OA\Tag(
    name: 'Admin - Inquiries',
    description: 'Admin inquiry/CRM management endpoints'
)]

// ============================================
// Response Schemas
// ============================================

#[OA\Schema(
    schema: 'RE_PaginationMeta',
    title: 'Pagination Meta',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 100),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 7),
        new OA\Property(property: 'from', type: 'integer', example: 1),
        new OA\Property(property: 'to', type: 'integer', example: 15),
    ]
)]

#[OA\Schema(
    schema: 'RE_PriceRange',
    title: 'Price Range',
    properties: [
        new OA\Property(property: 'min', type: 'number', format: 'float', example: 500000),
        new OA\Property(property: 'max', type: 'number', format: 'float', example: 2500000),
        new OA\Property(property: 'currency', type: 'string', example: 'EGP'),
    ]
)]

#[OA\Schema(
    schema: 'RE_AreaSize',
    title: 'Area Size',
    properties: [
        new OA\Property(property: 'min', type: 'number', format: 'float', example: 100),
        new OA\Property(property: 'max', type: 'number', format: 'float', example: 500),
        new OA\Property(property: 'unit', type: 'string', example: 'sqm'),
    ]
)]

#[OA\Schema(
    schema: 'RE_GeoLocation',
    title: 'Geo Location',
    properties: [
        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 30.0444),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 31.2357),
    ]
)]

#[OA\Schema(
    schema: 'RE_SuccessResponse',
    title: 'Success Response',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully'),
        new OA\Property(property: 'data', type: 'object'),
    ]
)]

#[OA\Schema(
    schema: 'RE_ErrorResponse',
    title: 'Error Response',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'An error occurred'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ]
)]

#[OA\Schema(
    schema: 'RE_ValidationErrorResponse',
    title: 'Validation Error Response',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['title' => ['The title field is required.']]
        ),
    ]
)]

// ============================================
// Property-specific Schemas
// ============================================

#[OA\Schema(
    schema: 'RE_PropertyListItem',
    title: 'Property List Item',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Modern Villa in New Cairo'),
        new OA\Property(property: 'slug', type: 'string', example: 'modern-villa-in-new-cairo'),
        new OA\Property(property: 'url', type: 'string', example: '/realestate/properties/1-modern-villa-in-new-cairo'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 2500000),
        new OA\Property(property: 'price_formatted', type: 'string', example: '2,500,000 EGP'),
        new OA\Property(property: 'purpose', type: 'string', enum: ['sale', 'rent'], example: 'sale'),
        new OA\Property(property: 'area_size', type: 'number', format: 'float', example: 350),
        new OA\Property(property: 'bedrooms', type: 'integer', example: 4),
        new OA\Property(property: 'bathrooms', type: 'integer', example: 3),
        new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
        new OA\Property(property: 'is_featured', type: 'boolean', example: true),
        new OA\Property(property: 'location', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'full_path', type: 'string'),
        ]),
        new OA\Property(property: 'property_type', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]

#[OA\Schema(
    schema: 'RE_PropertyDetail',
    title: 'Property Detail',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/RE_PropertyListItem'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'address', type: 'string'),
                new OA\Property(property: 'finishing', type: 'string', enum: ['unfinished', 'semi_finished', 'fully_finished', 'furnished']),
                new OA\Property(property: 'delivery_date', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'reference_number', type: 'string', nullable: true),
                new OA\Property(property: 'year_built', type: 'integer', nullable: true),
                new OA\Property(property: 'floor_number', type: 'integer', nullable: true),
                new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'video_url', type: 'string', nullable: true),
                new OA\Property(property: 'virtual_tour_url', type: 'string', nullable: true),
                new OA\Property(
                    property: 'amenities',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RE_AmenityResource')
                ),
                new OA\Property(
                    property: 'images',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RE_PropertyImageResource')
                ),
                new OA\Property(property: 'compound', ref: '#/components/schemas/RE_CompoundResource', nullable: true),
                new OA\Property(property: 'developer', ref: '#/components/schemas/RE_DeveloperResource', nullable: true),
                new OA\Property(property: 'views_count', type: 'integer'),
                new OA\Property(property: 'meta', type: 'object', properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'keywords', type: 'string'),
                ]),
            ]
        ),
    ]
)]

// ============================================
// Compound-specific Schemas
// ============================================

#[OA\Schema(
    schema: 'RE_CompoundListItem',
    title: 'Compound List Item',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Tierra Compound'),
        new OA\Property(property: 'slug', type: 'string', example: 'tierra-compound'),
        new OA\Property(property: 'url', type: 'string', example: '/realestate/compounds/1-tierra-compound'),
        new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['planning', 'under_construction', 'completed']),
        new OA\Property(property: 'delivery_year', type: 'integer', example: 2025),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'properties_count', type: 'integer', example: 45),
        new OA\Property(property: 'price_range', ref: '#/components/schemas/RE_PriceRange'),
        new OA\Property(property: 'location', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'full_path', type: 'string'),
        ]),
        new OA\Property(property: 'developer', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'logo', type: 'string'),
        ]),
    ]
)]

// ============================================
// Search Schemas
// ============================================

#[OA\Schema(
    schema: 'RE_SearchFilters',
    title: 'Search Filters',
    properties: [
        new OA\Property(property: 'q', type: 'string', description: 'Search query'),
        new OA\Property(property: 'area_id', type: 'integer'),
        new OA\Property(property: 'compound_id', type: 'integer'),
        new OA\Property(property: 'property_type_id', type: 'integer'),
        new OA\Property(property: 'developer_id', type: 'integer'),
        new OA\Property(property: 'purpose', type: 'string', enum: ['sale', 'rent']),
        new OA\Property(property: 'min_price', type: 'number'),
        new OA\Property(property: 'max_price', type: 'number'),
        new OA\Property(property: 'min_area', type: 'number'),
        new OA\Property(property: 'max_area', type: 'number'),
        new OA\Property(property: 'bedrooms', type: 'integer'),
        new OA\Property(property: 'bathrooms', type: 'integer'),
        new OA\Property(property: 'finishing', type: 'string', enum: ['unfinished', 'semi_finished', 'fully_finished', 'furnished']),
        new OA\Property(property: 'amenities', type: 'string', description: 'Comma-separated amenity IDs'),
        new OA\Property(property: 'featured', type: 'boolean'),
    ]
)]

#[OA\Schema(
    schema: 'RE_AutocompleteSuggestion',
    title: 'Autocomplete Suggestion',
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['area', 'compound', 'developer', 'property']),
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'label', type: 'string', example: 'New Cairo'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Super Area'),
        new OA\Property(property: 'url', type: 'string', example: '/realestate/areas/new-cairo'),
    ]
)]

#[OA\Schema(
    schema: 'RE_SearchFacets',
    title: 'Search Facets',
    properties: [
        new OA\Property(
            property: 'property_types',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]
            )
        ),
        new OA\Property(
            property: 'areas',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]
            )
        ),
        new OA\Property(
            property: 'developers',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]
            )
        ),
        new OA\Property(property: 'price_range', ref: '#/components/schemas/RE_PriceRange'),
        new OA\Property(property: 'area_range', ref: '#/components/schemas/RE_AreaSize'),
        new OA\Property(
            property: 'bedrooms',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'value', type: 'integer'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]
            )
        ),
    ]
)]

// ============================================
// Inquiry Schemas
// ============================================

#[OA\Schema(
    schema: 'RE_InquirySubmission',
    title: 'Inquiry Submission',
    required: ['name', 'email', 'phone'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '+20123456789'),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: 'I am interested in this property'),
        new OA\Property(property: 'preferred_contact_method', type: 'string', enum: ['phone', 'email', 'whatsapp']),
        new OA\Property(property: 'preferred_contact_time', type: 'string', nullable: true),
    ]
)]

#[OA\Schema(
    schema: 'RE_InquiryResponse',
    title: 'Inquiry Response',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Thank you for your inquiry. An agent will contact you shortly.'),
        new OA\Property(property: 'data', type: 'object', properties: [
            new OA\Property(property: 'inquiry_id', type: 'integer'),
            new OA\Property(property: 'expected_response_time', type: 'string', example: '24 hours'),
        ]),
    ]
)]

// ============================================
// Admin Statistics Schemas
// ============================================

#[OA\Schema(
    schema: 'RE_PropertyStatistics',
    title: 'Property Statistics',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 150),
        new OA\Property(property: 'published', type: 'integer', example: 120),
        new OA\Property(property: 'draft', type: 'integer', example: 20),
        new OA\Property(property: 'featured', type: 'integer', example: 15),
        new OA\Property(property: 'for_sale', type: 'integer', example: 100),
        new OA\Property(property: 'for_rent', type: 'integer', example: 50),
        new OA\Property(
            property: 'by_type',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]
            )
        ),
        new OA\Property(
            property: 'by_area',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'area', type: 'string'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]
            )
        ),
    ]
)]

#[OA\Schema(
    schema: 'RE_InquiryStatistics',
    title: 'Inquiry Statistics',
    properties: [
        new OA\Property(property: 'total', type: 'integer'),
        new OA\Property(property: 'new', type: 'integer'),
        new OA\Property(property: 'contacted', type: 'integer'),
        new OA\Property(property: 'qualified', type: 'integer'),
        new OA\Property(property: 'converted', type: 'integer'),
        new OA\Property(property: 'closed', type: 'integer'),
        new OA\Property(property: 'today', type: 'integer'),
        new OA\Property(property: 'this_week', type: 'integer'),
        new OA\Property(property: 'this_month', type: 'integer'),
        new OA\Property(
            property: 'by_source',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'source', type: 'string'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]
            )
        ),
    ]
)]

class RealEstateSwaggerInfo
{
    // This class serves as a container for OpenAPI annotations
}

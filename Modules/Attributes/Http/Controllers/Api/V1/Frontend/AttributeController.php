<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Attributes\Entities\Category;
use Modules\Attributes\Entities\SubCategory;
use Modules\Attributes\Entities\ChildCategory;
use Modules\Attributes\Entities\Brand;
use Modules\Attributes\Entities\Color;
use Modules\Attributes\Entities\Size;
use Modules\Attributes\Entities\Tag;
use Modules\Attributes\Http\Resources\CategoryResource;
use Modules\Attributes\Http\Resources\BrandResource;
use Modules\Attributes\Http\Resources\ColorResource;
use Modules\Attributes\Http\Resources\SizeResource;
use Modules\Attributes\Http\Resources\TagResource;
use OpenApi\Attributes as OA;

/**
 * Frontend Attribute Controller
 *
 * Handles public attribute listing for e-commerce storefront.
 *
 * @package Modules\Attributes\Http\Controllers\Api\V1\Frontend
 */
#[OA\Tag(name: 'Attributes', description: 'Product attributes (categories, brands, colors, sizes, tags)')]
class AttributeController extends Controller
{
    /**
     * Get all categories with hierarchy
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/attributes/categories',
        summary: 'Get all categories',
        description: 'Returns all active categories with their sub-categories and child-categories',
        tags: ['Attributes'],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CategoryResource'))
                    ]
                )
            )
        ]
    )]
    public function categories(): JsonResponse
    {
        $categories = Category::with(['image', 'status'])
            ->whereHas('status', fn($q) => $q->where('name', 'active'))
            ->get()
            ->map(function ($category) {
                $category->sub_categories = SubCategory::with(['image', 'status'])
                    ->where('category_id', $category->id)
                    ->whereHas('status', fn($q) => $q->where('name', 'active'))
                    ->get()
                    ->map(function ($subCategory) {
                        $subCategory->child_categories = ChildCategory::with(['image', 'status'])
                            ->where('sub_category_id', $subCategory->id)
                            ->whereHas('status', fn($q) => $q->where('name', 'active'))
                            ->get();
                        return $subCategory;
                    });
                return $category;
            });

        return response()->json([
            'success' => true,
            'message' => __('Categories retrieved successfully'),
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Get all brands
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/attributes/brands',
        summary: 'Get all brands',
        description: 'Returns all active brands',
        tags: ['Attributes'],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Brands retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BrandResource'))
                    ]
                )
            )
        ]
    )]
    public function brands(): JsonResponse
    {
        $brands = Brand::with(['logo', 'banner'])->get();

        return response()->json([
            'success' => true,
            'message' => __('Brands retrieved successfully'),
            'data' => BrandResource::collection($brands),
        ]);
    }

    /**
     * Get all colors
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/attributes/colors',
        summary: 'Get all colors',
        description: 'Returns all available colors',
        tags: ['Attributes'],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Colors retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ColorResource'))
                    ]
                )
            )
        ]
    )]
    public function colors(): JsonResponse
    {
        $colors = Color::all();

        return response()->json([
            'success' => true,
            'message' => __('Colors retrieved successfully'),
            'data' => ColorResource::collection($colors),
        ]);
    }

    /**
     * Get all sizes
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/attributes/sizes',
        summary: 'Get all sizes',
        description: 'Returns all available sizes',
        tags: ['Attributes'],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sizes retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SizeResource'))
                    ]
                )
            )
        ]
    )]
    public function sizes(): JsonResponse
    {
        $sizes = Size::all();

        return response()->json([
            'success' => true,
            'message' => __('Sizes retrieved successfully'),
            'data' => SizeResource::collection($sizes),
        ]);
    }

    /**
     * Get all tags
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/attributes/tags',
        summary: 'Get all tags',
        description: 'Returns all available tags',
        tags: ['Attributes'],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tags retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TagResource'))
                    ]
                )
            )
        ]
    )]
    public function tags(): JsonResponse
    {
        $tags = Tag::all();

        return response()->json([
            'success' => true,
            'message' => __('Tags retrieved successfully'),
            'data' => TagResource::collection($tags),
        ]);
    }
}

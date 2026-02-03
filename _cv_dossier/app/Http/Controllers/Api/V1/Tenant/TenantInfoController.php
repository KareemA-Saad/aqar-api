<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Tenant Info Controller
 *
 * Provides information about the current tenant context.
 *
 * @package App\Http\Controllers\Api\V1\Tenant
 */
#[OA\Tag(
    name: 'Tenant Context',
    description: 'Tenant context information endpoints. Access tenant-specific data and settings within the active tenant database.'
)]
final class TenantInfoController extends BaseApiController
{
    /**
     * Get tenant context information.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/info',
        summary: 'Get tenant context info',
        description: 'Retrieve information about the current tenant context including settings, package info, features, and expiration. This endpoint requires active tenant context.',
        security: [['sanctum_user' => []], ['sanctum_tenant_user' => []]],
        tags: ['Tenant Context']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Tenant context information retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant context active'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'tenant_id',
                            type: 'string',
                            example: 'acme-corp',
                            description: 'Current tenant identifier'
                        ),
                        new OA\Property(
                            property: 'settings',
                            type: 'object',
                            description: 'Tenant settings and configuration',
                            example: ['theme' => 'default', 'locale' => 'en', 'timezone' => 'UTC']
                        ),
                        new OA\Property(
                            property: 'package',
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Premium Plan'),
                                new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                                new OA\Property(property: 'status', type: 'string', example: 'active'),
                                new OA\Property(property: 'start_date', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
                                new OA\Property(property: 'expire_date', type: 'string', format: 'date-time', example: '2024-12-31T23:59:59.000000Z'),
                            ],
                            type: 'object',
                            description: 'Active package/subscription information'
                        ),
                        new OA\Property(
                            property: 'remaining_days',
                            type: 'integer',
                            example: 45,
                            description: 'Days remaining until package expires'
                        ),
                        new OA\Property(
                            property: 'features',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'blog'),
                            description: 'Available features for this tenant based on active package',
                            example: ['blog', 'eCommerce', 'inventory', 'support']
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Tenant context not initialized or package expired',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant package has expired'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Tenant not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant not found'),
            ]
        )
    )]
    public function info(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Tenant context active',
            'data' => [
                'tenant_id' => tenant_id(),
                'settings' => tenant_settings(),
                'package' => tenant_package_info(),
                'remaining_days' => tenant_remaining_days(),
                'features' => tenant_features(),
            ],
        ]);
    }
}


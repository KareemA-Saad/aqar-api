<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

/**
 * Base API Controller for Version 1
 *
 * All V1 API controllers should extend this base controller.
 * It provides API response methods and common functionality.
 *
 * @package App\Http\Controllers\Api\V1
 */
#[OA\Info(
    version: '1.0.0',
    title: 'AQAR API Documentation',
    description: 'Multi-tenant real estate management API with three authentication guards: Admin, User (Landlord/Tenant Owner), and Tenant User.',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'support@aqar.com'
    )
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local Development Server'
)]
#[OA\Server(
    url: 'https://api.aqar.com',
    description: 'Production Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum_admin',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Admin authentication token. Login via /api/v1/admin/auth/login to obtain token.'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum_user',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'User (Landlord/Tenant Owner) authentication token. Login via /api/v1/auth/login to obtain token.'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum_tenant_user',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Tenant user authentication token. Login via /api/v1/tenant/{tenant}/auth/login to obtain token.'
)]
#[OA\Tag(
    name: 'Admin Authentication',
    description: 'Authentication endpoints for platform administrators (Guard: api_admin)'
)]
#[OA\Tag(
    name: 'User Authentication',
    description: 'Authentication endpoints for users/landlords/tenant owners (Guard: api_user)'
)]
#[OA\Tag(
    name: 'Tenant User Authentication',
    description: 'Authentication endpoints for tenant users (Guard: api_tenant_user)'
)]
#[OA\Tag(
    name: 'Health Check',
    description: 'API health and status endpoints'
)]
class BaseApiController extends Controller
{
    use ApiResponse;

    /**
     * The default number of items per page for pagination.
     *
     * @var int
     */
    protected int $perPage = 15;

    /**
     * Get the number of items per page from request or use default.
     *
     * @return int
     */
    protected function getPerPage(): int
    {
        $perPage = (int) request()->input('per_page', $this->perPage);

        // Limit per_page to prevent abuse
        return min($perPage, 100);
    }
}


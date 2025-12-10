<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\PricePlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Tenant Controller
 *
 * Handles tenant CRUD operations for landlord users (tenant owners).
 * Users can create, manage, and switch between their tenants.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'Tenant Management',
    description: 'Tenant management endpoints for landlord users (Guard: api_user). Manage tenants/properties owned by the authenticated user.'
)]
final class TenantController extends BaseApiController
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    /**
     * List all tenants owned by authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenants',
        summary: 'List user\'s tenants',
        description: 'Get all tenants/properties owned by the authenticated user',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Management']
    )]
    #[OA\Response(
        response: 200,
        description: 'Tenants retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenants retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TenantResource')
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
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenants = $this->tenantService->getUserTenants($user);

        return $this->success(
            TenantResource::collection($tenants),
            'Tenants retrieved successfully'
        );
    }

    /**
     * Create a new tenant.
     *
     * @param CreateTenantRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenants',
        summary: 'Create new tenant',
        description: 'Create a new tenant/property. Database setup is asynchronous and may take a few moments.',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Management']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['subdomain', 'plan_id'],
            properties: [
                new OA\Property(
                    property: 'subdomain',
                    type: 'string',
                    example: 'acme-corp',
                    description: 'Unique subdomain identifier (lowercase, alphanumeric with hyphens)'
                ),
                new OA\Property(
                    property: 'plan_id',
                    type: 'integer',
                    example: 1,
                    description: 'Price plan ID'
                ),
                new OA\Property(
                    property: 'theme',
                    type: 'string',
                    example: 'default',
                    description: 'Theme name (optional)',
                    nullable: true
                ),
                new OA\Property(
                    property: 'theme_code',
                    type: 'string',
                    example: '#FF5733',
                    description: 'Theme color code (optional)',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Tenant created successfully (database setup in progress)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant created successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'tenant', ref: '#/components/schemas/TenantResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Tenant created. Database setup is in progress.'),
                        new OA\Property(property: 'database_status', type: 'string', example: 'pending'),
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
        response: 422,
        description: 'Validation error (subdomain taken, invalid plan, etc.)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                new OA\Property(
                    property: 'errors',
                    properties: [
                        new OA\Property(
                            property: 'subdomain',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'This subdomain is already taken.')
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Server error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Failed to create tenant: Database error'),
            ]
        )
    )]
    public function store(CreateTenantRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $data = $request->validatedData();

        // Get the price plan
        $plan = PricePlan::findOrFail($data['plan_id']);

        // Check if subdomain already exists
        if (Tenant::find($data['subdomain'])) {
            return $this->validationError(
                ['subdomain' => ['This subdomain is already taken.']],
                'Validation failed'
            );
        }

        try {
            $tenant = $this->tenantService->createTenant(
                user: $user,
                plan: $plan,
                data: $data,
                async: true // Use queued job for database creation
            );

            return $this->created([
                'tenant' => new TenantResource($tenant->load(['domains', 'paymentLog'])),
                'message' => 'Tenant created. Database setup is in progress.',
                'database_status' => 'pending',
            ], 'Tenant created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create tenant: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a specific tenant.
     *
     * @param string $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenants/{id}',
        summary: 'Get tenant details',
        description: 'Retrieve details of a specific tenant owned by the authenticated user, including database status',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Tenant retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'tenant', ref: '#/components/schemas/TenantResource'),
                        new OA\Property(
                            property: 'database_status',
                            type: 'string',
                            enum: ['ready', 'pending'],
                            example: 'ready',
                            description: 'Tenant database status'
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
        response: 404,
        description: 'Tenant not found or access denied',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant not found or access denied'),
            ]
        )
    )]
    public function show(string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenant = $this->tenantService->getTenant($id, $user);

        if (!$tenant) {
            return $this->notFound('Tenant not found or access denied');
        }

        // Check database status
        $databaseExists = $this->tenantService->databaseExists($tenant);

        return $this->success([
            'tenant' => new TenantResource($tenant),
            'database_status' => $databaseExists ? 'ready' : 'pending',
        ], 'Tenant retrieved successfully');
    }

    /**
     * Update tenant settings.
     *
     * @param UpdateTenantRequest $request
     * @param string $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: '/api/v1/tenants/{id}',
        summary: 'Update tenant settings',
        description: 'Update settings of a specific tenant owned by the authenticated user',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'instruction_status',
                    type: 'string',
                    example: 'active',
                    description: 'Tenant status'
                ),
                new OA\Property(
                    property: 'theme',
                    type: 'string',
                    example: 'modern',
                    description: 'Theme name',
                    nullable: true
                ),
                new OA\Property(
                    property: 'theme_code',
                    type: 'string',
                    example: '#3498DB',
                    description: 'Theme color code',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Tenant updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/TenantResource'),
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
        response: 404,
        description: 'Tenant not found or access denied',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant not found or access denied'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    example: ['field' => ['Error message']]
                ),
            ]
        )
    )]
    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenant = $this->tenantService->getTenant($id, $user);

        if (!$tenant) {
            return $this->notFound('Tenant not found or access denied');
        }

        $data = $request->validatedData();
        $tenant = $this->tenantService->updateTenant($tenant, $data);

        return $this->success(
            new TenantResource($tenant->load(['domains', 'paymentLog'])),
            'Tenant updated successfully'
        );
    }

    /**
     * Delete a tenant.
     *
     * @param string $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/tenants/{id}',
        summary: 'Delete tenant',
        description: 'Permanently delete a tenant and its database. This action cannot be undone.',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Tenant deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant deleted successfully'),
                new OA\Property(property: 'data', type: 'null', example: null),
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
        response: 404,
        description: 'Tenant not found or access denied',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant not found or access denied'),
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Server error (failed to delete)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Failed to delete tenant: Error details'),
            ]
        )
    )]
    public function destroy(string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenant = $this->tenantService->getTenant($id, $user);

        if (!$tenant) {
            return $this->notFound('Tenant not found or access denied');
        }

        try {
            $this->tenantService->deleteTenant($tenant);

            return $this->success(null, 'Tenant deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete tenant: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Switch tenant context and get new token.
     *
     * Issues a new token scoped to the specified tenant.
     *
     * @param string $id
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenants/{id}/switch',
        summary: 'Switch tenant context (new token)',
        description: 'Switch to a specific tenant context and receive a new token scoped to that tenant. Use this token to access tenant-specific resources.',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Tenant context switched successfully - use the new token for tenant-specific requests',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant context switched successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'tenant', ref: '#/components/schemas/TenantResource'),
                        new OA\Property(property: 'token', type: 'string', example: '3|abcdef123456...', description: 'New tenant-scoped token'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2024-12-31T23:59:59.000000Z'),
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
        response: 404,
        description: 'Tenant not found or access denied',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant not found or access denied'),
            ]
        )
    )]
    #[OA\Response(
        response: 425,
        description: 'Too Early - Tenant database is not ready yet',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant database is not ready yet. Please wait.'),
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Server error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Failed to switch tenant: Error details'),
            ]
        )
    )]
    public function switchTenant(string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenant = $this->tenantService->getTenant($id, $user);

        if (!$tenant) {
            return $this->notFound('Tenant not found or access denied');
        }

        // Check if database is ready
        if (!$this->tenantService->databaseExists($tenant)) {
            return $this->error('Tenant database is not ready yet. Please wait.', 425);
        }

        try {
            $tokenData = $this->tenantService->generateTenantToken($user, $tenant);

            return $this->success([
                'tenant' => new TenantResource($tenant->load(['domains', 'paymentLog'])),
                'token' => $tokenData['token'],
                'token_type' => 'Bearer',
                'expires_at' => $tokenData['expires_at'],
            ], 'Tenant context switched successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to switch tenant: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check tenant database status.
     *
     * @param string $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenants/{id}/database-status',
        summary: 'Check DB status',
        description: 'Check if the tenant database has been created and is ready for use',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Database status retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Database status retrieved'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'tenant_id', type: 'string', example: 'acme-corp'),
                        new OA\Property(
                            property: 'database_status',
                            type: 'string',
                            enum: ['ready', 'pending'],
                            example: 'ready',
                            description: 'Database creation status'
                        ),
                        new OA\Property(property: 'is_ready', type: 'boolean', example: true, description: 'Boolean flag for ready status'),
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
        response: 404,
        description: 'Tenant not found or access denied',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant not found or access denied'),
            ]
        )
    )]
    public function databaseStatus(string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenant = $this->tenantService->getTenant($id, $user);

        if (!$tenant) {
            return $this->notFound('Tenant not found or access denied');
        }

        $databaseExists = $this->tenantService->databaseExists($tenant);

        return $this->success([
            'tenant_id' => $tenant->id,
            'database_status' => $databaseExists ? 'ready' : 'pending',
            'is_ready' => $databaseExists,
        ], 'Database status retrieved');
    }

    /**
     * Manually trigger database setup (admin or retry).
     *
     * @param string $id
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenants/{id}/setup-database',
        summary: 'Manual DB setup',
        description: 'Manually trigger tenant database setup. Use this if automatic setup failed or to retry database creation.',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Database setup completed or already exists',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Database setup completed successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'tenant_id', type: 'string', example: 'acme-corp'),
                        new OA\Property(property: 'database_status', type: 'string', example: 'ready'),
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
        response: 404,
        description: 'Tenant not found or access denied',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant not found or access denied'),
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Database setup failed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Database setup failed: Error details'),
            ]
        )
    )]
    public function setupDatabase(string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenant = $this->tenantService->getTenant($id, $user);

        if (!$tenant) {
            return $this->notFound('Tenant not found or access denied');
        }

        if ($this->tenantService->databaseExists($tenant)) {
            return $this->success([
                'tenant_id' => $tenant->id,
                'database_status' => 'ready',
            ], 'Database already exists');
        }

        try {
            $this->tenantService->setupTenantDatabase($tenant);

            return $this->success([
                'tenant_id' => $tenant->id,
                'database_status' => 'ready',
            ], 'Database setup completed successfully');
        } catch (\Exception $e) {
            return $this->error('Database setup failed: ' . $e->getMessage(), 500);
        }
    }
}


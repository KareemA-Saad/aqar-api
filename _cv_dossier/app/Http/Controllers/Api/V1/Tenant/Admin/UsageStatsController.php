<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Tenant;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Usage Stats Controller
 *
 * Provides usage statistics and plan limit information for tenant admin dashboard.
 *
 * @package App\Http\Controllers\Api\V1\Tenant\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin Dashboard',
    description: 'Tenant admin dashboard endpoints for statistics, charts, and reports'
)]
final class UsageStatsController extends BaseApiController
{
    public function __construct(
        private readonly PlanLimitService $limitService
    ) {}

    /**
     * Get usage statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/usage-stats',
        summary: 'Get plan usage statistics',
        description: 'Get current usage statistics for all modules compared to plan limits. Shows used/limit for blogs, products, services, storage, etc.',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Dashboard'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usage statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Usage statistics retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'plan',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Basic Monthly'),
                                        new OA\Property(property: 'type', type: 'string', example: 'Monthly'),
                                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 50.00),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'limits',
                                    type: 'object',
                                    additionalProperties: new OA\AdditionalProperties(
                                        properties: [
                                            new OA\Property(property: 'used', type: 'integer', example: 15),
                                            new OA\Property(property: 'limit', type: 'integer', nullable: true, example: 20),
                                            new OA\Property(property: 'percentage', type: 'integer', example: 75),
                                            new OA\Property(property: 'unlimited', type: 'boolean', example: false),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'warnings',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['Products limit almost reached (90%)', 'Storage limit almost reached (83%)']
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Tenant not found'),
        ]
    )]
    public function index(string $tenant): JsonResponse
    {
        $tenantModel = Tenant::with(['paymentLog.package'])->find($tenant);

        if ($tenantModel === null) {
            return $this->notFound('Tenant not found');
        }

        $paymentLog = $tenantModel->paymentLog;
        $plan = $paymentLog?->package;

        // Get usage statistics
        $limits = $this->limitService->getUsageStats($tenantModel);
        $warnings = $this->limitService->getUsageWarnings($tenantModel);

        return $this->success([
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->title,
                'type' => $plan->getTypeLabel(),
                'price' => (float) $plan->price,
            ] : null,
            'subscription' => $paymentLog ? [
                'status' => $tenantModel->subscription_status ?? 'active',
                'started_at' => $paymentLog->start_date?->toISOString(),
                'expires_at' => $paymentLog->expire_date?->toISOString(),
                'is_trial' => $paymentLog->status === 'trial',
            ] : null,
            'limits' => $limits,
            'warnings' => $warnings,
        ], 'Usage statistics retrieved successfully');
    }

    /**
     * Get usage for a specific module.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/usage-stats/{module}',
        summary: 'Get usage for specific module',
        description: 'Get current usage statistics for a specific module',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Dashboard'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'module',
                in: 'path',
                required: true,
                description: 'Module name (blog, product, service, etc.)',
                schema: new OA\Schema(type: 'string', enum: ['blog', 'product', 'service', 'portfolio', 'job', 'event', 'donation', 'knowledgebase', 'appointment', 'campaign', 'storage'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Module usage retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Module usage retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'module', type: 'string', example: 'blog'),
                                new OA\Property(property: 'used', type: 'integer', example: 15),
                                new OA\Property(property: 'limit', type: 'integer', nullable: true, example: 20),
                                new OA\Property(property: 'remaining', type: 'integer', example: 5),
                                new OA\Property(property: 'percentage', type: 'integer', example: 75),
                                new OA\Property(property: 'unlimited', type: 'boolean', example: false),
                                new OA\Property(property: 'can_create', type: 'boolean', example: true),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid module'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Tenant not found'),
        ]
    )]
    public function show(string $tenant, string $module): JsonResponse
    {
        $tenantModel = Tenant::find($tenant);

        if ($tenantModel === null) {
            return $this->notFound('Tenant not found');
        }

        $module = strtolower($module);

        if (!PlanLimitService::isModuleSupported($module)) {
            return $this->error('Invalid module. Supported modules: ' . implode(', ', PlanLimitService::getSupportedModules()), 400);
        }

        $limit = $this->limitService->getPlanLimit($tenantModel, $module);
        $used = $this->limitService->getCurrentUsage($tenantModel, $module);
        $remaining = $this->limitService->getRemainingLimit($tenantModel, $module);
        $unlimited = $limit === -1;

        return $this->success([
            'module' => $module,
            'used' => $used,
            'limit' => $unlimited ? null : $limit,
            'remaining' => $unlimited ? null : $remaining,
            'percentage' => $unlimited ? 0 : ($limit > 0 ? min(100, (int) round(($used / $limit) * 100)) : 0),
            'unlimited' => $unlimited,
            'can_create' => $this->limitService->canCreate($tenantModel, $module),
        ], 'Module usage retrieved successfully');
    }
}

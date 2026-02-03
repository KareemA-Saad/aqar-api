<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Tenant\DashboardStatsResource;
use App\Services\Tenant\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Dashboard Controller
 *
 * Handles dashboard statistics and data for tenant admin panel.
 *
 * @package App\Http\Controllers\Api\V1\Tenant\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin Dashboard',
    description: 'Tenant admin dashboard endpoints for statistics, charts, and reports'
)]
final class DashboardController extends BaseApiController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Get dashboard statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/dashboard',
        summary: 'Get dashboard statistics',
        description: 'Get comprehensive dashboard statistics including orders, users, revenue, and growth data',
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
                description: 'Dashboard stats retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Dashboard statistics retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/DashboardStatsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(): JsonResponse
    {
        $stats = $this->dashboardService->getStats();

        return $this->success(
            new DashboardStatsResource($stats),
            'Dashboard statistics retrieved successfully'
        );
    }

    /**
     * Get revenue chart data.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/dashboard/revenue-chart',
        summary: 'Get revenue chart data',
        description: 'Get revenue data for charts based on selected period (daily, weekly, monthly, yearly)',
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
                name: 'period',
                in: 'query',
                required: false,
                description: 'Chart period',
                schema: new OA\Schema(type: 'string', enum: ['daily', 'weekly', 'monthly', 'yearly'], default: 'monthly')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Revenue chart data retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Revenue chart data retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'period', type: 'string', example: 'monthly'),
                                new OA\Property(property: 'labels', type: 'array', items: new OA\Items(type: 'string'), example: ['Jan 2025', 'Feb 2025']),
                                new OA\Property(
                                    property: 'datasets',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'label', type: 'string', example: 'Revenue'),
                                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'number'), example: [1500.00, 2000.00]),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(property: 'total', type: 'number', format: 'float', example: 3500.00),
                                new OA\Property(property: 'average', type: 'number', format: 'float', example: 1750.00),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function revenueChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly');
        
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            $period = 'monthly';
        }

        $data = $this->dashboardService->getRevenueData($period);

        return $this->success($data, 'Revenue chart data retrieved successfully');
    }

    /**
     * Get orders chart data.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/dashboard/orders-chart',
        summary: 'Get orders chart data',
        description: 'Get orders data for charts based on selected period (daily, weekly, monthly, yearly)',
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
                name: 'period',
                in: 'query',
                required: false,
                description: 'Chart period',
                schema: new OA\Schema(type: 'string', enum: ['daily', 'weekly', 'monthly', 'yearly'], default: 'monthly')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Orders chart data retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Orders chart data retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'period', type: 'string', example: 'monthly'),
                                new OA\Property(property: 'labels', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'datasets', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'total', type: 'integer', example: 150),
                                new OA\Property(property: 'average', type: 'number', format: 'float', example: 12.5),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function ordersChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly');
        
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            $period = 'monthly';
        }

        $data = $this->dashboardService->getOrdersData($period);

        return $this->success($data, 'Orders chart data retrieved successfully');
    }

    /**
     * Get top selling products.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/dashboard/top-products',
        summary: 'Get top selling products',
        description: 'Get a list of best selling products by quantity sold',
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
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Number of products to return',
                schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 50)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Top products retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Top products retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Product Name'),
                                    new OA\Property(property: 'slug', type: 'string', example: 'product-name'),
                                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                                    new OA\Property(property: 'total_sold', type: 'integer', example: 150),
                                    new OA\Property(property: 'total_revenue', type: 'number', format: 'float', example: 14998.50),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function topProducts(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);
        $limit = max($limit, 1);

        $products = $this->dashboardService->getTopProducts($limit);

        return $this->success($products, 'Top products retrieved successfully');
    }

    /**
     * Get recent orders.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/dashboard/recent-orders',
        summary: 'Get recent orders',
        description: 'Get a list of the most recent orders',
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
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Number of orders to return',
                schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 50)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Recent orders retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Recent orders retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                                    new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                    new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 199.99),
                                    new OA\Property(property: 'payment_status', type: 'string', example: 'complete'),
                                    new OA\Property(property: 'order_status', type: 'string', example: 'pending'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function recentOrders(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);
        $limit = max($limit, 1);

        $orders = $this->dashboardService->getRecentOrders($limit);

        return $this->success($orders, 'Recent orders retrieved successfully');
    }

    /**
     * Get low stock products.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/dashboard/low-stock',
        summary: 'Get low stock products',
        description: 'Get a list of products with low inventory levels',
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
                name: 'threshold',
                in: 'query',
                required: false,
                description: 'Stock threshold to consider as low',
                schema: new OA\Schema(type: 'integer', default: 10, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Low stock products retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Low stock products retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Product Name'),
                                    new OA\Property(property: 'slug', type: 'string', example: 'product-name'),
                                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                                    new OA\Property(property: 'stock_count', type: 'integer', example: 5),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function lowStock(Request $request): JsonResponse
    {
        $threshold = max((int) $request->get('threshold', 10), 1);

        $products = $this->dashboardService->getLowStockProducts($threshold);

        return $this->success($products, 'Low stock products retrieved successfully');
    }

    /**
     * Get recent activity.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/dashboard/recent-activity',
        summary: 'Get recent activity',
        description: 'Get a feed of recent activity including orders, user registrations, and content updates',
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
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Number of activities to return',
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Recent activity retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Recent activity retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'type', type: 'string', example: 'order'),
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'New Order'),
                                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 199.99, nullable: true),
                                    new OA\Property(property: 'status', type: 'string', example: 'complete'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function recentActivity(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 20), 100);
        $limit = max($limit, 1);

        $activity = $this->dashboardService->getRecentActivity($limit);

        return $this->success($activity, 'Recent activity retrieved successfully');
    }
}

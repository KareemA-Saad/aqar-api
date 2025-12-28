<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\OrderResource;
use Modules\Product\Http\Resources\OrderCollection;
use Modules\Product\Services\OrderService;
use OpenApi\Attributes as OA;

/**
 * Admin Order Controller
 *
 * Order management endpoints for tenant administrators.
 *
 * @package Modules\Product\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Orders',
    description: 'Order management endpoints for tenant administrators'
)]
final class OrderController extends BaseApiController
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * List all orders.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/orders',
        summary: 'List all orders',
        description: 'Get paginated list of all orders with filters',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Orders']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by order status',
        schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'shipped', 'delivered', 'complete', 'cancel'])
    )]
    #[OA\Parameter(
        name: 'payment_status',
        in: 'query',
        description: 'Filter by payment status',
        schema: new OA\Schema(type: 'string', enum: ['pending', 'paid', 'failed', 'refunded'])
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Search by customer name, email, or order number',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'date_from',
        in: 'query',
        description: 'Filter from date (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'date_to',
        in: 'query',
        description: 'Filter to date (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Orders retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Orders retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/OrderCollection'),
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'payment_status',
            'user_id',
            'search',
            'date_from',
            'date_to',
            'sort_by',
            'sort_order',
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $orders = $this->orderService->getAllOrders($filters, $perPage);

        return $this->success(
            new OrderCollection($orders),
            'Orders retrieved successfully'
        );
    }

    /**
     * Get a single order.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/orders/{id}',
        summary: 'Get a single order',
        description: 'Get detailed information about an order',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Orders']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Order ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Order retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Order retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/OrderResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Order not found'
    )]
    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderById($id);

            return $this->success(
                new OrderResource($order),
                'Order retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        }
    }

    /**
     * Update order status.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/orders/{id}/status',
        summary: 'Update order status',
        description: 'Update the status of an order',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Orders']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Order ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(
                    property: 'status',
                    type: 'string',
                    enum: ['pending', 'in_progress', 'shipped', 'delivered', 'complete', 'cancel'],
                    example: 'in_progress'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Order status updated successfully'
    )]
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,shipped,delivered,complete,cancel'],
        ]);

        try {
            $order = $this->orderService->updateStatus($id, $request->input('status'));

            return $this->success(
                new OrderResource($order),
                'Order status updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Update payment status.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/orders/{id}/payment-status',
        summary: 'Update payment status',
        description: 'Update the payment status of an order',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Orders']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Order ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['payment_status'],
            properties: [
                new OA\Property(
                    property: 'payment_status',
                    type: 'string',
                    enum: ['pending', 'paid', 'failed', 'refunded'],
                    example: 'paid'
                ),
                new OA\Property(property: 'transaction_id', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment status updated successfully'
    )]
    public function updatePaymentStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'payment_status' => ['required', 'string', 'in:pending,paid,failed,refunded'],
            'transaction_id' => ['nullable', 'string'],
        ]);

        try {
            $order = $this->orderService->updatePaymentStatus(
                $id,
                $request->input('payment_status'),
                $request->input('transaction_id')
            );

            return $this->success(
                new OrderResource($order),
                'Payment status updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Cancel an order.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/orders/{id}/cancel',
        summary: 'Cancel an order',
        description: 'Cancel an order and restore inventory',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Orders']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Order ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Customer requested cancellation'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Order cancelled successfully'
    )]
    #[OA\Response(
        response: 422,
        description: 'Cannot cancel this order'
    )]
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->cancelOrder($id, $request->input('reason'));

            return $this->success(
                new OrderResource($order),
                'Order cancelled successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get order statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/orders/statistics',
        summary: 'Get order statistics',
        description: 'Get order and revenue statistics',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Orders']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistics retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'total_orders', type: 'integer', example: 150),
                    new OA\Property(property: 'total_revenue', type: 'number', example: 15000.00),
                    new OA\Property(property: 'pending_orders', type: 'integer', example: 10),
                    new OA\Property(property: 'processing_orders', type: 'integer', example: 25),
                    new OA\Property(property: 'completed_orders', type: 'integer', example: 100),
                    new OA\Property(property: 'cancelled_orders', type: 'integer', example: 15),
                    new OA\Property(property: 'paid_orders', type: 'integer', example: 125),
                ]),
            ]
        )
    )]
    public function statistics(): JsonResponse
    {
        $stats = $this->orderService->getOrderStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }
}

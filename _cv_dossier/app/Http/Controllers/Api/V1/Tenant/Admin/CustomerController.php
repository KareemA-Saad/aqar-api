<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Tenant\Customer\CustomerStoreRequest;
use App\Http\Requests\Tenant\Customer\CustomerUpdateRequest;
use App\Http\Requests\Tenant\Customer\CustomerPasswordRequest;
use App\Http\Resources\Tenant\CustomerResource;
use App\Http\Resources\Tenant\CustomerCollection;
use App\Mail\BasicMail;
use App\Models\TenantUser;
use App\Services\Tenant\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant Admin Customer Controller
 *
 * Manages customers (frontend users) within a tenant context.
 * Requires tenant admin authentication and tenant context.
 *
 * @package App\Http\Controllers\Api\V1\Tenant\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Customers',
    description: 'Manage customers/frontend users within a tenant'
)]
final class CustomerController extends BaseApiController
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    /**
     * List all customers with pagination and filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/customers',
        summary: 'List customers',
        description: 'Get paginated list of customers with optional search, filter, and sort options',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Search by name, email, mobile, or username',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'email_verified',
        in: 'query',
        description: 'Filter by email verification status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'created_from',
        in: 'query',
        description: 'Filter by creation date from (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'created_to',
        in: 'query',
        description: 'Filter by creation date to (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'Sort field',
        schema: new OA\Schema(type: 'string', enum: ['name', 'email', 'created_at', 'updated_at'])
    )]
    #[OA\Parameter(
        name: 'sort_order',
        in: 'query',
        description: 'Sort direction',
        schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Customers retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Customers retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CustomerResource')
                        ),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'email_verified',
            'status',
            'created_from',
            'created_to',
            'sort_by',
            'sort_order',
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $customers = $this->customerService->getCustomers($filters, $perPage);

        return $this->success(
            new CustomerCollection($customers),
            'Customers retrieved successfully'
        );
    }

    /**
     * Get customer statistics.
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/customers/statistics',
        summary: 'Get customer statistics',
        description: 'Get overview statistics for tenant customers',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistics retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Statistics retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'total_customers', type: 'integer', example: 150),
                        new OA\Property(property: 'verified_customers', type: 'integer', example: 120),
                        new OA\Property(property: 'unverified_customers', type: 'integer', example: 30),
                        new OA\Property(property: 'new_this_month', type: 'integer', example: 25),
                        new OA\Property(property: 'new_today', type: 'integer', example: 5),
                        new OA\Property(property: 'verification_rate', type: 'number', format: 'float', example: 80.0),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function statistics(): JsonResponse
    {
        $stats = $this->customerService->getStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Create a new customer.
     *
     * @param CustomerStoreRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/customers',
        summary: 'Create customer',
        description: 'Create a new customer in the tenant',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CustomerStoreRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Customer created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Customer created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CustomerResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return $this->created(
            new CustomerResource($customer),
            'Customer created successfully'
        );
    }

    /**
     * Get a specific customer.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/customers/{id}',
        summary: 'Get customer details',
        description: 'Get detailed information about a specific customer',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Customer ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Customer retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Customer retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CustomerResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Customer not found')]
    public function show(int $id): JsonResponse
    {
        $customer = TenantUser::find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        return $this->success(
            new CustomerResource($customer),
            'Customer retrieved successfully'
        );
    }

    /**
     * Update a customer.
     *
     * @param CustomerUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/customers/{id}',
        summary: 'Update customer',
        description: 'Update an existing customer',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Customer ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CustomerUpdateRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Customer updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Customer updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CustomerResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Customer not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(CustomerUpdateRequest $request, int $id): JsonResponse
    {
        $customer = TenantUser::find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        $updated = $this->customerService->updateCustomer($customer, $request->validated());

        return $this->success(
            new CustomerResource($updated),
            'Customer updated successfully'
        );
    }

    /**
     * Delete a customer.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/customers/{id}',
        summary: 'Delete customer',
        description: 'Delete a customer from the tenant',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Customer ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Customer deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Customer deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Customer not found')]
    public function destroy(int $id): JsonResponse
    {
        $customer = TenantUser::find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        $this->customerService->deleteCustomer($customer);

        return $this->success(null, 'Customer deleted successfully');
    }

    /**
     * Toggle customer status.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Patch(
        path: '/api/v1/tenant/{tenant}/admin/customers/{id}/toggle-status',
        summary: 'Toggle customer status',
        description: 'Toggle customer active/inactive status',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Customer ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Customer status toggled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Customer status toggled successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CustomerResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Customer not found')]
    public function toggleStatus(int $id): JsonResponse
    {
        $customer = TenantUser::find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        $updated = $this->customerService->toggleStatus($customer);

        return $this->success(
            new CustomerResource($updated),
            'Customer status toggled successfully'
        );
    }

    /**
     * Update customer password.
     *
     * @param CustomerPasswordRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/customers/{id}/change-password',
        summary: 'Change customer password',
        description: 'Update a customer password (admin action)',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Customer ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecure123!'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecure123!'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Password updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Password updated successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Customer not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function changePassword(CustomerPasswordRequest $request, int $id): JsonResponse
    {
        $customer = TenantUser::find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        $this->customerService->updatePassword($customer, $request->input('password'));

        return $this->success(null, 'Password updated successfully');
    }

    /**
     * Get customer order history.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/customers/{id}/orders',
        summary: 'Get customer order history',
        description: 'Get paginated order history for a specific customer',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Customer ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Order history retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Order history retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Customer not found')]
    public function orders(Request $request, int $id): JsonResponse
    {
        $customer = TenantUser::find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $orders = $this->customerService->getOrderHistory($customer, $perPage);

        return $this->success([
            'items' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 'Order history retrieved successfully');
    }

    /**
     * Send email to customer.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/customers/{id}/send-email',
        summary: 'Send email to customer',
        description: 'Send a custom email to a specific customer',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Customer ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['subject', 'message'],
            properties: [
                new OA\Property(property: 'subject', type: 'string', example: 'Important Update'),
                new OA\Property(property: 'message', type: 'string', example: 'Hello, we have an important update for you...'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Email sent successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Email sent successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Customer not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function sendEmail(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $customer = TenantUser::find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        try {
            Mail::to($customer->email)->queue(
                new BasicMail($request->input('message'), $request->input('subject'))
            );

            return $this->success(null, 'Email sent successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to send email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Resend email verification.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/customers/{id}/resend-verification',
        summary: 'Resend verification email',
        description: 'Resend email verification to customer',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Customer ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Verification email sent successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Verification email sent successfully'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Email already verified')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Customer not found')]
    public function resendVerification(int $id): JsonResponse
    {
        $customer = TenantUser::find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        if ($customer->email_verified) {
            return $this->error('Email is already verified', 400);
        }

        $token = $this->customerService->generateVerificationToken($customer);

        try {
            // Send verification email
            $message = "Please verify your email. Your verification code is: {$token}";
            Mail::to($customer->email)->queue(
                new BasicMail($message, 'Email Verification')
            );

            return $this->success(null, 'Verification email sent successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to send verification email', 500);
        }
    }

    /**
     * Export customers to CSV.
     *
     * @param Request $request
     * @return StreamedResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/customers/export',
        summary: 'Export customers to CSV',
        description: 'Export all customers to a CSV file',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Customers']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Filter by search term',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'email_verified',
        in: 'query',
        description: 'Filter by email verification status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Response(
        response: 200,
        description: 'CSV file download',
        content: new OA\MediaType(
            mediaType: 'text/csv',
            schema: new OA\Schema(type: 'string', format: 'binary')
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'email_verified']);
        $customers = $this->customerService->exportCustomers($filters);

        $filename = 'customers_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'ID',
                'Name',
                'Email',
                'Username',
                'Mobile',
                'Address',
                'City',
                'State',
                'Country',
                'Email Verified',
                'Created At',
            ]);

            // Data rows
            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer['id'],
                    $customer['name'],
                    $customer['email'],
                    $customer['username'],
                    $customer['mobile'],
                    $customer['address'],
                    $customer['city'],
                    $customer['state'],
                    $customer['country'],
                    $customer['email_verified'],
                    $customer['created_at'],
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}

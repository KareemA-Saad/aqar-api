<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\PaymentLogResource;
use App\Http\Resources\SupportTicketResource;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserDashboardResource;
use App\Http\Resources\UserResource;
use App\Models\PricePlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * User Dashboard Controller (Self-Service)
 *
 * Handles user self-service operations including profile management,
 * tenant creation, and dashboard data.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'User Dashboard',
    description: 'User self-service endpoints (Guard: api_user). Manage own profile, tenants, and view dashboard.'
)]
final class UserDashboardController extends BaseApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get dashboard statistics.
     */
    #[OA\Get(
        path: '/api/v1/dashboard',
        summary: 'Get dashboard stats',
        description: 'Get user dashboard statistics including tenant count, active packages, and support tickets',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Response(
        response: 200,
        description: 'Dashboard data retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Dashboard data retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserDashboardResource'),
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
    public function dashboard(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $stats = $this->userService->getUserDashboardStats($user);

        return $this->success(
            new UserDashboardResource($stats),
            'Dashboard data retrieved'
        );
    }

    /**
     * Get own profile.
     */
    #[OA\Get(
        path: '/api/v1/profile',
        summary: 'Get own profile',
        description: 'Get the authenticated user\'s profile',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Profile retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
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
    public function profile(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $user->load(['tenants', 'latestPaymentLog']);

        return $this->success(
            new UserResource($user),
            'Profile retrieved'
        );
    }

    /**
     * Update own profile.
     */
    #[OA\Put(
        path: '/api/v1/profile',
        summary: 'Update own profile',
        description: 'Update the authenticated user\'s profile',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UserUpdateProfileRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
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
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        try {
            $data = $request->validatedData();
            $updatedUser = $this->userService->updateProfile($user, $data);

            return $this->success(
                new UserResource($updatedUser),
                'Profile updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Change password.
     */
    #[OA\Post(
        path: '/api/v1/profile/change-password',
        summary: 'Change password',
        description: 'Change the authenticated user\'s password. This will revoke all existing tokens.',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ChangePasswordRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Password changed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Password changed successfully. Please login again.'),
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
        response: 400,
        description: 'Invalid current password',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Current password is incorrect'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        try {
            $data = $request->validatedData();
            $this->userService->changePassword($user, $data['old_password'], $data['password']);

            return $this->success(null, 'Password changed successfully. Please login again.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error('Failed to change password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List own tenants.
     */
    #[OA\Get(
        path: '/api/v1/my-tenants',
        summary: 'List own tenants',
        description: 'Get list of all tenants owned by the authenticated user with status',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Response(
        response: 200,
        description: 'Tenants retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenants retrieved'),
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
    public function tenants(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenants = $this->userService->getUserTenants($user);

        return $this->success(
            TenantResource::collection($tenants),
            'Tenants retrieved'
        );
    }

    /**
     * Create a new tenant.
     */
    #[OA\Post(
        path: '/api/v1/my-tenants',
        summary: 'Create new tenant',
        description: 'Create a new tenant with plan selection. Database setup is asynchronous.',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['subdomain', 'plan_id'],
            properties: [
                new OA\Property(
                    property: 'subdomain',
                    type: 'string',
                    example: 'my-property',
                    description: 'Unique subdomain (lowercase, alphanumeric with hyphens)'
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
                    nullable: true
                ),
                new OA\Property(
                    property: 'theme_code',
                    type: 'string',
                    example: '#3498DB',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Tenant created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant created successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'tenant', ref: '#/components/schemas/TenantResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Database setup is in progress.'),
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
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function createTenant(CreateTenantRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $data = $request->validatedData();

        // Check if subdomain already exists
        if (Tenant::find($data['subdomain'])) {
            return $this->validationError(
                ['subdomain' => ['This subdomain is already taken.']],
                'Validation failed'
            );
        }

        // Get the price plan
        $plan = PricePlan::find($data['plan_id']);

        if (!$plan) {
            return $this->validationError(
                ['plan_id' => ['Selected price plan does not exist.']],
                'Validation failed'
            );
        }

        try {
            $tenant = $this->userService->createTenantForUser($user, $plan, $data);

            return $this->created([
                'tenant' => new TenantResource($tenant->load(['domains', 'paymentLog'])),
                'message' => 'Database setup is in progress.',
                'database_status' => 'pending',
            ], 'Tenant created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create tenant: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get own support tickets.
     */
    #[OA\Get(
        path: '/api/v1/my-tickets',
        summary: 'List own support tickets',
        description: 'Get paginated list of support tickets created by the authenticated user',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 10)
    )]
    #[OA\Response(
        response: 200,
        description: 'Support tickets retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Support tickets retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SupportTicketResource')
                ),
                new OA\Property(property: 'pagination', type: 'object'),
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
    public function supportTickets(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $perPage = (int) $request->query('per_page', 10);
        $tickets = $this->userService->getUserSupportTickets($user, $perPage);

        return $this->paginated(
            $tickets,
            SupportTicketResource::class,
            'Support tickets retrieved'
        );
    }

    /**
     * Get payment history.
     */
    #[OA\Get(
        path: '/api/v1/my-payments',
        summary: 'Get own payment history',
        description: 'Get paginated payment history for the authenticated user',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment history retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Payment history retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PaymentLogResource')
                ),
                new OA\Property(property: 'pagination', type: 'object'),
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
    public function paymentHistory(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $perPage = (int) $request->query('per_page', $this->perPage);
        $payments = $this->userService->getPaymentHistory($user, $perPage);

        return $this->paginated(
            $payments,
            PaymentLogResource::class,
            'Payment history retrieved'
        );
    }
}


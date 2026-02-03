<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\User\AdminUpdateUserRequest;
use App\Http\Resources\PaymentLogResource;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * User Controller (Admin)
 *
 * Handles user management operations by administrators.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'User Management (Admin)',
    description: 'Admin endpoints to manage landlord users (Guard: api_admin). Manage tenant owners and their subscriptions.'
)]
final class UserController extends BaseApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get paginated list of users.
     */
    #[OA\Get(
        path: '/api/v1/admin/users',
        summary: 'List all users',
        description: 'Get paginated list of all landlord users with tenant info',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'Search by name, email, username, or company',
        schema: new OA\Schema(type: 'string', example: 'john')
    )]
    #[OA\Parameter(
        name: 'has_subdomain',
        in: 'query',
        required: false,
        description: 'Filter by users with/without tenants',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'email_verified',
        in: 'query',
        required: false,
        description: 'Filter by email verification status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page (max 100)',
        schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Users retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Users retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/UserResource')
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 4),
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
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'has_subdomain' => $request->has('has_subdomain') ? $request->boolean('has_subdomain') : null,
            'email_verified' => $request->has('email_verified') ? $request->boolean('email_verified') : null,
            'per_page' => (int) $request->query('per_page', $this->perPage),
        ];

        // Remove null values
        $filters = array_filter($filters, fn ($v) => $v !== null);

        $users = $this->userService->getUserList($filters);

        return $this->paginated(
            $users,
            UserResource::class,
            'Users retrieved successfully'
        );
    }

    /**
     * Get user details with all tenants and payment history.
     */
    #[OA\Get(
        path: '/api/v1/admin/users/{user}',
        summary: 'Get user details',
        description: 'Retrieve details of a specific user with all tenants and payment history',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'User retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User retrieved successfully'),
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
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    public function show(int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        return $this->success(
            new UserResource($userModel),
            'User retrieved successfully'
        );
    }

    /**
     * Update user details.
     */
    #[OA\Put(
        path: '/api/v1/admin/users/{user}',
        summary: 'Update user',
        description: 'Update user details by administrator',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AdminUpdateUserRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'User updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User updated successfully'),
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
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
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
    public function update(AdminUpdateUserRequest $request, int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        try {
            $data = $request->validatedData();
            $updatedUser = $this->userService->updateUser($userModel, $data);

            return $this->success(
                new UserResource($updatedUser),
                'User updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Deactivate user (revoke tokens).
     */
    #[OA\Delete(
        path: '/api/v1/admin/users/{user}',
        summary: 'Deactivate user',
        description: 'Deactivate a user by revoking all their tokens',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'User deactivated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User deactivated successfully'),
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
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    public function destroy(int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        try {
            $this->userService->deactivateUser($userModel);

            return $this->success(null, 'User deactivated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to deactivate user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Impersonate user and get token.
     */
    #[OA\Post(
        path: '/api/v1/admin/users/{user}/impersonate',
        summary: 'Impersonate user',
        description: 'Get a token to act as this user. Token expires in 2 hours.',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Impersonation token generated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Impersonation token generated'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
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
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    public function impersonate(int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        try {
            $tokenData = $this->userService->generateImpersonationToken($userModel);

            return $this->success([
                'user' => new UserResource($userModel),
                'token' => $tokenData['token'],
                'token_type' => 'Bearer',
                'expires_at' => $tokenData['expires_at'],
            ], 'Impersonation token generated');
        } catch (\Exception $e) {
            return $this->error('Failed to generate impersonation token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user's payment history.
     */
    #[OA\Get(
        path: '/api/v1/admin/users/{user}/payments',
        summary: 'Get user payment history',
        description: 'Get paginated payment history for a specific user',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
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
                new OA\Property(
                    property: 'pagination',
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
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    public function paymentHistory(Request $request, int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        $perPage = (int) $request->query('per_page', $this->perPage);
        $payments = $this->userService->getPaymentHistory($userModel, $perPage);

        return $this->paginated(
            $payments,
            PaymentLogResource::class,
            'Payment history retrieved'
        );
    }
}


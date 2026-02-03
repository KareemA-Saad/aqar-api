<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Tenant\Customer\CustomerProfileRequest;
use App\Http\Requests\Tenant\Customer\AddressRequest;
use App\Http\Resources\Tenant\CustomerDashboardResource;
use App\Http\Resources\Tenant\AddressResource;
use App\Http\Resources\Tenant\WishlistResource;
use App\Http\Resources\TenantUserResource;
use App\Models\TenantUser;
use App\Services\Tenant\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

/**
 * Customer Dashboard Controller
 *
 * Handles self-service operations for authenticated tenant customers.
 * Includes dashboard stats, profile management, orders, wishlist, and addresses.
 *
 * @package App\Http\Controllers\Api\V1\Tenant
 */
#[OA\Tag(
    name: 'Tenant Customer - Dashboard',
    description: 'Customer self-service endpoints for dashboard, profile, orders, wishlist, and addresses'
)]
final class CustomerDashboardController extends BaseApiController
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    /**
     * Get the authenticated customer.
     *
     * @return TenantUser|null
     */
    private function getAuthenticatedCustomer(): ?TenantUser
    {
        return auth('api_tenant_user')->user();
    }

    /**
     * Get customer dashboard overview.
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/user/dashboard',
        summary: 'Get customer dashboard',
        description: 'Get dashboard overview with stats for authenticated customer',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
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
        description: 'Dashboard data retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Dashboard data retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CustomerDashboardResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function dashboard(): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $stats = $this->customerService->getCustomerDashboard($customer);

        return $this->success(
            new CustomerDashboardResource([
                'customer' => $customer,
                'stats' => $stats,
            ]),
            'Dashboard data retrieved successfully'
        );
    }

    /**
     * Get customer profile.
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/user/profile',
        summary: 'Get customer profile',
        description: 'Get the authenticated customer profile details',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
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
        description: 'Profile retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Profile retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/TenantUserResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function profile(): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        return $this->success(
            new TenantUserResource($customer),
            'Profile retrieved successfully'
        );
    }

    /**
     * Update customer profile.
     *
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/user/profile',
        summary: 'Update customer profile',
        description: 'Update the authenticated customer profile details',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
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
        content: new OA\JsonContent(ref: '#/components/schemas/CustomerProfileRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/TenantUserResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function updateProfile(CustomerProfileRequest $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $updated = $this->customerService->updateCustomer($customer, $request->validated());

        return $this->success(
            new TenantUserResource($updated),
            'Profile updated successfully'
        );
    }

    /**
     * Change customer password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/user/change-password',
        summary: 'Change customer password',
        description: 'Change the authenticated customer password',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
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
        content: new OA\JsonContent(
            required: ['current_password', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'CurrentPass123!'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecure123!'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecure123!'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Password changed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Password changed successfully'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Current password is incorrect')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function changePassword(Request $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($request->input('current_password'), $customer->password)) {
            return $this->error('Current password is incorrect', 400);
        }

        $this->customerService->updatePassword($customer, $request->input('password'));

        return $this->success(null, 'Password changed successfully');
    }

    /**
     * Get customer orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/user/orders',
        summary: 'Get customer orders',
        description: 'Get paginated list of customer orders',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
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
    public function orders(Request $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
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
        ], 'Orders retrieved successfully');
    }

    /**
     * Get customer wishlist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/user/wishlist',
        summary: 'Get customer wishlist',
        description: 'Get paginated list of wishlist items',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Wishlist retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Wishlist retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/WishlistResource')
                        ),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function wishlist(Request $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $wishlist = $this->customerService->getWishlist($customer, $perPage);

        return $this->success([
            'items' => WishlistResource::collection($wishlist),
            'pagination' => [
                'current_page' => $wishlist->currentPage(),
                'last_page' => $wishlist->lastPage(),
                'per_page' => $wishlist->perPage(),
                'total' => $wishlist->total(),
            ],
        ], 'Wishlist retrieved successfully');
    }

    /**
     * Add item to wishlist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/user/wishlist',
        summary: 'Add to wishlist',
        description: 'Add a product to the customer wishlist',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
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
        content: new OA\JsonContent(
            required: ['product_id'],
            properties: [
                new OA\Property(property: 'product_id', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Product added to wishlist successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Product added to wishlist successfully'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Product already in wishlist')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function addToWishlist(Request $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $request->validate([
            'product_id' => 'required|integer',
        ]);

        $added = $this->customerService->addToWishlist(
            $customer,
            (int) $request->input('product_id')
        );

        if (!$added) {
            return $this->error('Product is already in your wishlist', 400);
        }

        return $this->created(null, 'Product added to wishlist successfully');
    }

    /**
     * Remove item from wishlist.
     *
     * @param int $productId
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/user/wishlist/{productId}',
        summary: 'Remove from wishlist',
        description: 'Remove a product from the customer wishlist',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'productId',
        in: 'path',
        required: true,
        description: 'Product ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Product removed from wishlist successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Product removed from wishlist successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Product not found in wishlist')]
    public function removeFromWishlist(int $productId): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $removed = $this->customerService->removeFromWishlist($customer, $productId);

        if (!$removed) {
            return $this->notFound('Product not found in wishlist');
        }

        return $this->success(null, 'Product removed from wishlist successfully');
    }

    /**
     * Clear wishlist.
     *
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/user/wishlist',
        summary: 'Clear wishlist',
        description: 'Remove all items from the customer wishlist',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
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
        description: 'Wishlist cleared successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Wishlist cleared successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'items_removed', type: 'integer', example: 5),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function clearWishlist(): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $count = $this->customerService->clearWishlist($customer);

        return $this->success([
            'items_removed' => $count,
        ], 'Wishlist cleared successfully');
    }

    /**
     * Get customer addresses.
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/user/addresses',
        summary: 'Get customer addresses',
        description: 'Get list of saved addresses for the customer',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
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
        description: 'Addresses retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Addresses retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/AddressResource')
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function addresses(): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $addresses = $this->customerService->getAddresses($customer);

        return $this->success(
            AddressResource::collection($addresses),
            'Addresses retrieved successfully'
        );
    }

    /**
     * Add a new address.
     *
     * @param AddressRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/user/addresses',
        summary: 'Add address',
        description: 'Add a new address for the customer',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
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
        content: new OA\JsonContent(ref: '#/components/schemas/AddressRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Address added successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Address added successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AddressResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function addAddress(AddressRequest $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $address = $this->customerService->addAddress($customer, $request->validated());

        return $this->created(
            new AddressResource($address),
            'Address added successfully'
        );
    }

    /**
     * Update an address.
     *
     * @param AddressRequest $request
     * @param int $addressId
     * @return JsonResponse
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/user/addresses/{addressId}',
        summary: 'Update address',
        description: 'Update an existing customer address',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'addressId',
        in: 'path',
        required: true,
        description: 'Address ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AddressRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Address updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Address updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AddressResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Address not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function updateAddress(AddressRequest $request, int $addressId): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $address = $this->customerService->updateAddress($customer, $addressId, $request->validated());

        if (!$address) {
            return $this->notFound('Address not found');
        }

        return $this->success(
            new AddressResource($address),
            'Address updated successfully'
        );
    }

    /**
     * Delete an address.
     *
     * @param int $addressId
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/user/addresses/{addressId}',
        summary: 'Delete address',
        description: 'Delete a customer address',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'addressId',
        in: 'path',
        required: true,
        description: 'Address ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Address deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Address deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Address not found')]
    public function deleteAddress(int $addressId): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $deleted = $this->customerService->deleteAddress($customer, $addressId);

        if (!$deleted) {
            return $this->notFound('Address not found');
        }

        return $this->success(null, 'Address deleted successfully');
    }

    /**
     * Set address as default.
     *
     * @param int $addressId
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/user/addresses/{addressId}/set-default',
        summary: 'Set default address',
        description: 'Set an address as the default shipping address',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant Customer - Dashboard']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'addressId',
        in: 'path',
        required: true,
        description: 'Address ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Default address set successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Default address set successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Address not found')]
    public function setDefaultAddress(int $addressId): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return $this->unauthorized();
        }

        $success = $this->customerService->setDefaultAddress($customer, $addressId);

        if (!$success) {
            return $this->notFound('Address not found');
        }

        return $this->success(null, 'Default address set successfully');
    }
}

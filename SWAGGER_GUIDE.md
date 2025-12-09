# L5 Swagger Documentation Guide

## 📚 Overview

Your AQAR API is now equipped with **L5 Swagger** for comprehensive API documentation and testing. The Swagger UI provides an interactive interface to explore and test all your API endpoints.

---

## 🚀 Quick Start

### Access Swagger UI

Once your Laravel server is running, access the Swagger documentation at:

```
http://localhost:8000/api/documentation
```

Or in production:

```
https://your-domain.com/api/documentation
```

### Generate/Regenerate Documentation

Whenever you add or modify OpenAPI annotations, regenerate the documentation:

```bash
php artisan l5-swagger:generate
```

For development, automatic regeneration is enabled. Set in `.env`:

```env
L5_SWAGGER_GENERATE_ALWAYS=true
```

---

## 🔐 Authentication in Swagger UI

Your API uses **Laravel Sanctum** with **three separate guards**:

1. **sanctum_admin** - Platform administrators
2. **sanctum_user** - Users/Landlords/Tenant Owners  
3. **sanctum_tenant_user** - Tenant users

### How to Authenticate:

1. **Login First**: Use the appropriate login endpoint:
   - Admin: `POST /api/v1/admin/auth/login`
   - User: `POST /api/v1/auth/login`
   - Tenant User: `POST /api/v1/tenant/{tenant}/auth/login`

2. **Copy the Token**: From the response, copy the `token` value

3. **Authorize**: 
   - Click the **"Authorize"** button (🔒 icon) at the top
   - Select the appropriate security scheme
   - Enter: `Bearer YOUR_TOKEN_HERE` (with "Bearer " prefix)
   - Click **"Authorize"**

4. **Test Protected Endpoints**: All protected endpoints will now include your token

---

## 📝 How to Document Your Endpoints

### Basic Endpoint Documentation

Use PHP 8 attributes to document your controller methods:

```php
use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/endpoint',
    summary: 'Short description',
    description: 'Detailed description',
    tags: ['Tag Name']
)]
#[OA\Response(
    response: 200,
    description: 'Success response',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string', example: 'Success'),
        ]
    )
)]
public function yourMethod(): JsonResponse
{
    // Your code
}
```

### Protected Endpoints (Requires Authentication)

Add the `security` parameter:

```php
#[OA\Get(
    path: '/api/v1/admin/users',
    summary: 'List all users',
    security: [['sanctum_admin' => []]],  // 👈 This requires admin auth
    tags: ['Admin']
)]
```

Choose the appropriate security scheme:
- `sanctum_admin` for admin-only endpoints
- `sanctum_user` for user endpoints
- `sanctum_tenant_user` for tenant user endpoints

### Request Body Documentation

```php
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'email'],
        properties: [
            new OA\Property(
                property: 'name',
                type: 'string',
                example: 'John Doe',
                description: 'User full name'
            ),
            new OA\Property(
                property: 'email',
                type: 'string',
                format: 'email',
                example: 'john@example.com'
            ),
        ]
    )
)]
```

### Path Parameters

```php
#[OA\Get(
    path: '/api/v1/users/{id}',
    summary: 'Get user by ID',
    tags: ['Users']
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    description: 'User ID',
    schema: new OA\Schema(type: 'integer', example: 1)
)]
```

### Query Parameters

```php
#[OA\Get(
    path: '/api/v1/users',
    summary: 'List users with pagination',
    tags: ['Users']
)]
#[OA\Parameter(
    name: 'per_page',
    in: 'query',
    required: false,
    description: 'Items per page',
    schema: new OA\Schema(type: 'integer', example: 15, default: 15)
)]
#[OA\Parameter(
    name: 'page',
    in: 'query',
    required: false,
    description: 'Page number',
    schema: new OA\Schema(type: 'integer', example: 1, default: 1)
)]
```

---

## 🏗️ Documenting Resources (Schemas)

Create reusable schema definitions in your Resource classes:

```php
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserResource',
    title: 'User Resource',
    description: 'User data representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class UserResource extends JsonResource
{
    // Resource methods
}
```

Then reference it in your endpoints:

```php
#[OA\Response(
    response: 200,
    description: 'User retrieved',
    content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
)]
```

---

## 🎯 Complete Example: CRUD Endpoint

Here's a full example for a typical CRUD controller:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UserController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/users',
        summary: 'List all users',
        description: 'Get paginated list of users',
        security: [['sanctum_admin' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page (max: 100)',
        schema: new OA\Schema(type: 'integer', example: 15, default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Users list retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Users retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/UserResource')
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(): JsonResponse
    {
        $users = User::paginate($this->getPerPage());
        
        return $this->success(
            UserResource::collection($users),
            'Users retrieved'
        );
    }

    #[OA\Get(
        path: '/api/v1/users/{id}',
        summary: 'Get user by ID',
        security: [['sanctum_admin' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'User retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User found'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'User not found')]
    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        return $this->success(
            new UserResource($user),
            'User found'
        );
    }

    #[OA\Post(
        path: '/api/v1/users',
        summary: 'Create new user',
        security: [['sanctum_admin' => []]],
        tags: ['Users']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePass123!'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'User created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User created'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        
        return $this->success(
            new UserResource($user),
            'User created',
            201
        );
    }

    #[OA\Put(
        path: '/api/v1/users/{id}',
        summary: 'Update user',
        security: [['sanctum_admin' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'User updated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User updated'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update($request->validated());
        
        return $this->success(
            new UserResource($user),
            'User updated'
        );
    }

    #[OA\Delete(
        path: '/api/v1/users/{id}',
        summary: 'Delete user',
        security: [['sanctum_admin' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'User deleted',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User deleted'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'User not found')]
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return $this->success(null, 'User deleted');
    }
}
```

---

## 🔧 Configuration

### Environment Variables

Add to your `.env` file:

```env
# L5 Swagger Configuration
L5_SWAGGER_GENERATE_ALWAYS=true      # Auto-generate in development
L5_SWAGGER_CONST_HOST=http://localhost:8000
L5_FORMAT_TO_USE_FOR_DOCS=json       # json or yaml
L5_SWAGGER_UI_DOC_EXPANSION=list     # none, list, or full
L5_SWAGGER_UI_FILTERS=true           # Enable search filter
L5_SWAGGER_UI_PERSIST_AUTHORIZATION=false
```

### Production Settings

In production, disable auto-generation for better performance:

```env
L5_SWAGGER_GENERATE_ALWAYS=false
```

Then generate once during deployment:

```bash
php artisan l5-swagger:generate
```

---

## 📂 File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/
│   │   │   ├── BaseApiController.php    # OpenAPI base definitions
│   │   │   └── Auth/
│   │   │       └── AdminAuthController.php  # Example documented controller
│   │   └── HealthController.php
│   └── Resources/
│       └── AdminResource.php           # Example documented resource
config/
└── l5-swagger.php                      # L5 Swagger configuration
storage/
└── api-docs/
    ├── api-docs.json                   # Generated OpenAPI spec
    └── api-docs.yaml
```

---

## 🎨 Tags Organization

Organize your endpoints with tags:

```php
// In BaseApiController.php or dedicated controller
#[OA\Tag(name: 'Users', description: 'User management endpoints')]
#[OA\Tag(name: 'Products', description: 'Product management endpoints')]
#[OA\Tag(name: 'Orders', description: 'Order management endpoints')]
```

---

## 🐛 Troubleshooting

### Documentation Not Showing?

1. Clear Laravel cache:
```bash
php artisan cache:clear
php artisan config:clear
```

2. Regenerate documentation:
```bash
php artisan l5-swagger:generate
```

3. Check file permissions on `storage/api-docs/`

### Annotations Not Working?

- Ensure `use OpenApi\Attributes as OA;` is imported
- Check for syntax errors in attributes
- Verify PHP 8.1+ is being used

### Authentication Not Working?

- Ensure you include "Bearer " prefix with the token
- Check token expiration
- Verify the correct security scheme is used

---

## 📚 Resources

- [L5 Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
- [OpenAPI 3.0 Specification](https://swagger.io/specification/)
- [Swagger UI](https://swagger.io/tools/swagger-ui/)

---

## ✅ Already Documented Endpoints

The following endpoints are already fully documented:

### Admin Authentication
- ✅ `POST /api/v1/admin/auth/login` - Admin login
- ✅ `POST /api/v1/admin/auth/logout` - Admin logout
- ✅ `GET /api/v1/admin/auth/me` - Get admin profile
- ✅ `POST /api/v1/admin/auth/refresh-token` - Refresh token
- ✅ `POST /api/v1/admin/auth/forgot-password` - Request password reset
- ✅ `POST /api/v1/admin/auth/reset-password` - Reset password

### Health Check
- ✅ `GET /api/health` - API health check

---

## 🚀 Next Steps

1. **Test the documentation**: Visit `http://localhost:8000/api/documentation`
2. **Document remaining endpoints**: Add annotations to `UserAuthController` and `TenantUserAuthController`
3. **Create Resource schemas**: Document all your API resources
4. **Add more examples**: Include request/response examples for clarity
5. **Organize with tags**: Group related endpoints together

Happy documenting! 🎉


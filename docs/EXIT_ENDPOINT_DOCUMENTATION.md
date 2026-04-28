# Exit Tenant Endpoint Documentation

## Overview
The exit endpoint allows a central user to revoke their tenant admin token and cleanly exit a tenant's admin context.

## Endpoint Details

### Route
```
DELETE /api/v1/tenants/{tenant_id}/exit
```

### Authentication
- **Guard**: `api_user` (central database user)
- **Required**: Valid central user authentication token

### Parameters

| Name | Type | Location | Required | Example |
|------|------|----------|----------|---------|
| id/tenant_id | string | path | yes | `acme-corp` |

### Request Headers
```
Authorization: Bearer {central_user_token}
Content-Type: application/json
```

### Response (Success - 200)
```json
{
  "success": true,
  "message": "Tenant context exited successfully",
  "data": {
    "tenant_id": "acme-corp",
    "tokens_revoked": true
  }
}
```

### Response Codes

| Code | Scenario |
|------|----------|
| 200 | Successfully exited tenant context and revoked tokens |
| 401 | User not authenticated with central database |
| 404 | Tenant not found or access denied |
| 500 | Server error during token revocation |

### Error Responses

#### 401 - Unauthenticated
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

#### 404 - Tenant Not Found
```json
{
  "success": false,
  "message": "Tenant not found or access denied"
}
```

## Usage Flow

```
1. User is authenticated with central token (api_user guard)
   POST /api/v1/auth/login
   → Returns: central_token

2. User switches to tenant and gets admin token
   POST /api/v1/tenants/{tenant_id}/switch
   Auth: Bearer {central_token}
   → Returns: tenant_admin_token (7 day expiry)

3. User works with tenant admin endpoints
   GET /api/v1/tenant/{tenant_id}/admin/realestate/properties
   Auth: Bearer {tenant_admin_token}

4. User exits tenant context (optional but recommended)
   DELETE /api/v1/tenants/{tenant_id}/exit
   Auth: Bearer {central_token}
   → Revokes tenant_admin_token

5. User can still access central endpoints
   central_token remains valid
```

## What Happens When You Exit

When you call the exit endpoint:
- ✅ All tenant admin tokens for this user are revoked
- ✅ Central user token remains valid
- ✅ User cannot access tenant admin endpoints until they switch again
- ✅ User can still access central user endpoints

## Postman Integration

### To import in Postman:

1. Open Postman
2. Click **Import** → **Link**
3. Enter the Swagger URL or upload the OpenAPI spec
4. All endpoints including the exit endpoint will be available under "Tenant Management"

### In Postman Collections:
- **Folder**: Tenant Management
- **Endpoint**: Exit tenant context (DELETE)
- **URL**: `{{base_url}}/api/v1/tenants/{tenant_id}/exit`

## Implementation Notes

- The exit endpoint uses the **central user authentication** (api_user guard)
- It does NOT require the tenant admin token
- Tokens are revoked per-tenant (doesn't affect other tenants' tokens)
- The function is idempotent - calling it multiple times is safe

## Related Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/v1/tenants/{id}/switch` | Get tenant admin token |
| DELETE | `/api/v1/tenants/{id}/exit` | Revoke tenant admin token |
| GET | `/api/v1/tenants/{id}` | Get tenant details |
| PUT | `/api/v1/tenants/{id}` | Update tenant settings |
| DELETE | `/api/v1/tenants/{id}` | Delete tenant permanently |

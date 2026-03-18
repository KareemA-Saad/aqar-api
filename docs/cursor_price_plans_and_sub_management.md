# Price plans and sub management
_Exported on 12/20/2025 at 16:25:50 GMT+2 from Cursor (2.2.20)_

---

**User**

Create the Price Plan and Subscription management API.

Reference:@OLDARCHIVE/app/Http/Controllers/Landlord/Admin @OLDARCHIVE/app/Models 
@app/Http/Controllers/Landlord/Admin/PricePlanController.php
@app/Models/PricePlan.php
@app/Models/PlanFeature.php
@app/Models/PaymentLogs.php
@app/Http/Controllers/Landlord/Frontend/PaymentLogController.php

Create:

1. app/Http/Controllers/Api/V1/Landlord/Admin/PricePlanController.php
   - index() - all plans with features (for admin)
   - store(PricePlanRequest)
   - show(id) 
   - update(id, PricePlanRequest)
   - destroy(id)
   - toggleStatus(id)
   - reorderFeatures(id, ReorderRequest)

2. app/Http/Controllers/Api/V1/Landlord/PlanController.php (Public/User facing)
   - index() - active plans only with pricing
   - show(slug) - plan details for purchase page
   - compare() - comparison matrix of all plans
   - checkCoupon(code, planId) - validate coupon

3. app/Http/Controllers/Api/V1/Landlord/SubscriptionController.php
   - current() - user's active subscription
   - history() - all subscription history
   - initiate(planId) - start subscription flow, return payment options
   - complete(transactionData) - complete subscription
   - cancel(subscriptionId, reason)
   - renew(subscriptionId) - renew existing subscription
   - upgrade(planId) - upgrade to new plan (prorated)

4. app/Http/Requests/
   - StorePricePlanRequest.php
   - UpdatePricePlanRequest.php
   - InitiateSubscriptionRequest.php

5. app/Http/Resources/
   - PricePlanResource.php
   - PricePlanCollection.php
   - PlanFeatureResource.php
   - SubscriptionResource.php
   - PaymentLogResource.php

6. app/Services/SubscriptionService.php
   - calculatePrice(PricePlan $plan, ?Coupon $coupon): array
   - initiateSubscription(User $user, PricePlan $plan): PaymentLog
   - completeSubscription(PaymentLog $log, array $paymentData): Tenant
   - renewSubscription(PaymentLog $log): PaymentLog
   - cancelSubscription(PaymentLog $log, string $reason): bool

Reference payment flow from:@OLDARCHIVE/app/Http/Controllers/Landlord/Frontend/LandlordFrontendController.php @OLDARCHIVE/app/Helpers/Payment 
@app/Http/Controllers/Landlord/Frontend/LandlordFrontendController.php (plan_order, order_confirm methods)
@app/Helpers/Payment/DatabaseUpdateAndMailSend/LandlordPricePlanAndTenantCreate.php

---

**Cursor**

I'll start by retrieving relevant knowledge and reading the reference files to understand the existing patterns and implementations.

Let me read the reference files from the OLDARCHIVE folder:

Now let me check the existing API routes, resources, and base controller patterns:

Let me check a few more files to understand the patterns better:

Let me check the User model and understand the relations, and also check the plan type constants:

Now I have a comprehensive understanding of the codebase. Let me create the todo list and start implementing:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    /**
     * Discount type constants.
     */
    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    public const DISCOUNT_TYPE_AMOUNT = 'amount';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'code',
        'discount_amount',
        'discount_type',
        'status',
        'max_use_qty',
        'expire_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'decimal:2',
        'status' => 'boolean',
        'max_use_qty' => 'integer',
        'expire_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all logs for this coupon.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CouponLog::class, 'coupon_id', 'id');
    }

    /**
     * Check if coupon is valid (active and not expired).
     */
    public function isValid(): bool
    {
        return $this->status
            && ($this->expire_date === null || $this->expire_date->isFuture());
    }

    /**
     * Check if user has reached max usage limit.
     */
    public function hasReachedLimitForUser(int $userId): bool
    {
        if ($this->max_use_qty === null || $this->max_use_qty === 0) {
            return false;
        }

        $usageCount = $this->logs()->where('user_id', $userId)->count();

        return $usageCount >= $this->max_use_qty;
    }

    /**
     * Calculate discount for given price.
     */
    public function calculateDiscount(float $price): float
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            return round($price * ($this->discount_amount / 100), 2);
        }

        return min((float) $this->discount_amount, $price);
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'coupon_id',
        'user_id',
        'payment_log_id',
        'discount_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the coupon.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the payment log.
     */
    public function paymentLog(): BelongsTo
    {
        return $this->belongsTo(PaymentLog::class, 'payment_log_id', 'id');
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\PricePlan;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Store Price Plan Request DTO
 *
 * Validates data for creating a new price plan.
 */
#[OA\Schema(
    schema: 'StorePricePlanRequest',
    title: 'Store Price Plan Request',
    required: ['title', 'price', 'type', 'status'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Premium Plan', maxLength: 191),
        new OA\Property(property: 'subtitle', type: 'string', example: 'Best for growing businesses', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99, minimum: 0),
        new OA\Property(
            property: 'type',
            type: 'integer',
            description: '0 = Monthly, 1 = Yearly, 2 = Lifetime',
            enum: [0, 1, 2],
            example: 0
        ),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'zero_price', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'has_trial', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'trial_days', type: 'integer', example: 14, minimum: 0, nullable: true),
        new OA\Property(property: 'page_permission_feature', type: 'integer', example: 10, nullable: true, description: 'Max pages allowed'),
        new OA\Property(property: 'blog_permission_feature', type: 'integer', example: 50, nullable: true, description: 'Max blogs allowed'),
        new OA\Property(property: 'product_permission_feature', type: 'integer', example: 100, nullable: true, description: 'Max products allowed'),
        new OA\Property(property: 'portfolio_permission_feature', type: 'integer', example: 20, nullable: true, description: 'Max portfolio items'),
        new OA\Property(property: 'storage_permission_feature', type: 'integer', example: 1024, nullable: true, description: 'Storage limit in MB'),
        new OA\Property(property: 'appointment_permission_feature', type: 'integer', example: 100, nullable: true, description: 'Max appointments'),
        new OA\Property(
            property: 'features',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'feature_name', type: 'string', example: 'eCommerce'),
                    new OA\Property(property: 'status', type: 'boolean', example: true),
                ],
                type: 'object'
            ),
            nullable: true,
            description: 'Array of plan features'
        ),
        new OA\Property(
            property: 'faq',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'answer', type: 'string'),
                ],
                type: 'object'
            ),
            nullable: true
        ),
    ]
)]
final class StorePricePlanRequest extends FormRequest
{
    /**
     * Plan type constants.
     */
    public const TYPE_MONTHLY = 0;
    public const TYPE_YEARLY = 1;
    public const TYPE_LIFETIME = 2;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'integer', 'in:0,1,2'],
            'status' => ['required', 'boolean'],
            'zero_price' => ['nullable', 'boolean'],
            'has_trial' => ['nullable', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'page_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'blog_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'product_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'portfolio_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'storage_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'appointment_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'features' => ['nullable', 'array'],
            'features.*.feature_name' => ['required_with:features', 'string', 'max:191'],
            'features.*.status' => ['required_with:features', 'boolean'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['required_with:faq', 'string'],
            'faq.*.answer' => ['required_with:faq', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Plan title is required.',
            'price.required' => 'Plan price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'type.required' => 'Plan type is required.',
            'type.in' => 'Plan type must be 0 (Monthly), 1 (Yearly), or 2 (Lifetime).',
            'status.required' => 'Plan status is required.',
            'features.*.feature_name.required_with' => 'Feature name is required.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'price' => (float) $data['price'],
            'type' => (int) $data['type'],
            'status' => (bool) $data['status'],
            'zero_price' => (bool) ($data['zero_price'] ?? false),
            'has_trial' => (bool) ($data['has_trial'] ?? false),
            'trial_days' => $data['has_trial'] ?? false ? (int) ($data['trial_days'] ?? 0) : 0,
            'page_permission_feature' => isset($data['page_permission_feature']) ? (int) $data['page_permission_feature'] : null,
            'blog_permission_feature' => isset($data['blog_permission_feature']) ? (int) $data['blog_permission_feature'] : null,
            'product_permission_feature' => isset($data['product_permission_feature']) ? (int) $data['product_permission_feature'] : null,
            'portfolio_permission_feature' => isset($data['portfolio_permission_feature']) ? (int) $data['portfolio_permission_feature'] : null,
            'storage_permission_feature' => isset($data['storage_permission_feature']) ? (int) $data['storage_permission_feature'] : null,
            'appointment_permission_feature' => isset($data['appointment_permission_feature']) ? (int) $data['appointment_permission_feature'] : null,
            'features' => $data['features'] ?? [],
            'faq' => isset($data['faq']) ? serialize($data['faq']) : null,
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\PricePlan;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Update Price Plan Request DTO
 *
 * Validates data for updating an existing price plan.
 */
#[OA\Schema(
    schema: 'UpdatePricePlanRequest',
    title: 'Update Price Plan Request',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Premium Plan', maxLength: 191),
        new OA\Property(property: 'subtitle', type: 'string', example: 'Best for growing businesses', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99, minimum: 0),
        new OA\Property(
            property: 'type',
            type: 'integer',
            description: '0 = Monthly, 1 = Yearly, 2 = Lifetime',
            enum: [0, 1, 2],
            example: 0
        ),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'zero_price', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'has_trial', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'trial_days', type: 'integer', example: 14, minimum: 0, nullable: true),
        new OA\Property(property: 'page_permission_feature', type: 'integer', example: 10, nullable: true),
        new OA\Property(property: 'blog_permission_feature', type: 'integer', example: 50, nullable: true),
        new OA\Property(property: 'product_permission_feature', type: 'integer', example: 100, nullable: true),
        new OA\Property(property: 'portfolio_permission_feature', type: 'integer', example: 20, nullable: true),
        new OA\Property(property: 'storage_permission_feature', type: 'integer', example: 1024, nullable: true),
        new OA\Property(property: 'appointment_permission_feature', type: 'integer', example: 100, nullable: true),
        new OA\Property(
            property: 'features',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', nullable: true, description: 'Feature ID for update, null for new'),
                    new OA\Property(property: 'feature_name', type: 'string', example: 'eCommerce'),
                    new OA\Property(property: 'status', type: 'boolean', example: true),
                ],
                type: 'object'
            ),
            nullable: true
        ),
        new OA\Property(
            property: 'faq',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'answer', type: 'string'),
                ],
                type: 'object'
            ),
            nullable: true
        ),
    ]
)]
final class UpdatePricePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'type' => ['sometimes', 'required', 'integer', 'in:0,1,2'],
            'status' => ['sometimes', 'required', 'boolean'],
            'zero_price' => ['nullable', 'boolean'],
            'has_trial' => ['nullable', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'page_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'blog_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'product_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'portfolio_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'storage_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'appointment_permission_feature' => ['nullable', 'integer', 'min:-1'],
            'features' => ['nullable', 'array'],
            'features.*.id' => ['nullable', 'integer', 'exists:plan_features,id'],
            'features.*.feature_name' => ['required_with:features', 'string', 'max:191'],
            'features.*.status' => ['required_with:features', 'boolean'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['required_with:faq', 'string'],
            'faq.*.answer' => ['required_with:faq', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Plan title is required.',
            'price.numeric' => 'Price must be a valid number.',
            'type.in' => 'Plan type must be 0 (Monthly), 1 (Yearly), or 2 (Lifetime).',
            'features.*.id.exists' => 'Feature ID does not exist.',
            'features.*.feature_name.required_with' => 'Feature name is required.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();
        $result = [];

        if (isset($data['title'])) {
            $result['title'] = $data['title'];
        }
        if (array_key_exists('subtitle', $data)) {
            $result['subtitle'] = $data['subtitle'];
        }
        if (isset($data['price'])) {
            $result['price'] = (float) $data['price'];
        }
        if (isset($data['type'])) {
            $result['type'] = (int) $data['type'];
        }
        if (isset($data['status'])) {
            $result['status'] = (bool) $data['status'];
        }
        if (array_key_exists('zero_price', $data)) {
            $result['zero_price'] = (bool) ($data['zero_price'] ?? false);
        }
        if (array_key_exists('has_trial', $data)) {
            $result['has_trial'] = (bool) ($data['has_trial'] ?? false);
            $result['trial_days'] = $result['has_trial'] ? (int) ($data['trial_days'] ?? 0) : 0;
        }

        $permissionFields = [
            'page_permission_feature',
            'blog_permission_feature',
            'product_permission_feature',
            'portfolio_permission_feature',
            'storage_permission_feature',
            'appointment_permission_feature',
        ];

        foreach ($permissionFields as $field) {
            if (array_key_exists($field, $data)) {
                $result[$field] = $data[$field] !== null ? (int) $data[$field] : null;
            }
        }

        if (array_key_exists('features', $data)) {
            $result['features'] = $data['features'] ?? [];
        }

        if (array_key_exists('faq', $data)) {
            $result['faq'] = isset($data['faq']) ? serialize($data['faq']) : null;
        }

        return $result;
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\PricePlan;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Reorder Features Request DTO
 *
 * Validates data for reordering plan features.
 */
#[OA\Schema(
    schema: 'ReorderFeaturesRequest',
    title: 'Reorder Features Request',
    required: ['feature_ids'],
    properties: [
        new OA\Property(
            property: 'feature_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [3, 1, 2, 5, 4],
            description: 'Array of feature IDs in desired order'
        ),
    ]
)]
final class ReorderFeaturesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'feature_ids' => ['required', 'array', 'min:1'],
            'feature_ids.*' => ['required', 'integer', 'exists:plan_features,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'feature_ids.required' => 'Feature IDs are required.',
            'feature_ids.array' => 'Feature IDs must be an array.',
            'feature_ids.*.exists' => 'One or more feature IDs do not exist.',
        ];
    }

    /**
     * Get validated feature IDs.
     *
     * @return array<int, int>
     */
    public function getFeatureIds(): array
    {
        return array_map('intval', $this->validated('feature_ids'));
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Initiate Subscription Request DTO
 *
 * Validates data for initiating a new subscription.
 */
#[OA\Schema(
    schema: 'InitiateSubscriptionRequest',
    title: 'Initiate Subscription Request',
    required: ['plan_id', 'subdomain'],
    properties: [
        new OA\Property(property: 'plan_id', type: 'integer', example: 1, description: 'ID of the price plan'),
        new OA\Property(property: 'subdomain', type: 'string', example: 'mystore', maxLength: 63, description: 'Subdomain for tenant'),
        new OA\Property(property: 'theme', type: 'string', example: 'default', nullable: true, description: 'Theme slug'),
        new OA\Property(property: 'coupon_code', type: 'string', example: 'SAVE20', nullable: true, description: 'Coupon code for discount'),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe', nullable: true, description: 'Payment gateway identifier'),
        new OA\Property(property: 'is_trial', type: 'boolean', example: false, nullable: true, description: 'Start as trial subscription'),
    ]
)]
final class InitiateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:price_plans,id'],
            'subdomain' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/',
                'unique:tenants,id',
            ],
            'theme' => ['nullable', 'string', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'payment_gateway' => ['nullable', 'string', 'max:50'],
            'is_trial' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'Plan selection is required.',
            'plan_id.exists' => 'Selected plan does not exist.',
            'subdomain.required' => 'Subdomain is required.',
            'subdomain.regex' => 'Subdomain must contain only lowercase letters, numbers, and hyphens.',
            'subdomain.unique' => 'This subdomain is already taken.',
            'coupon_code.max' => 'Coupon code is too long.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'plan_id' => (int) $data['plan_id'],
            'subdomain' => strtolower(trim($data['subdomain'])),
            'theme' => $data['theme'] ?? null,
            'coupon_code' => $data['coupon_code'] ?? null,
            'payment_gateway' => $data['payment_gateway'] ?? null,
            'is_trial' => (bool) ($data['is_trial'] ?? false),
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Complete Subscription Request DTO
 *
 * Validates data for completing a subscription payment.
 */
#[OA\Schema(
    schema: 'CompleteSubscriptionRequest',
    title: 'Complete Subscription Request',
    required: ['payment_log_id', 'transaction_id'],
    properties: [
        new OA\Property(property: 'payment_log_id', type: 'integer', example: 1, description: 'ID of the payment log'),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'txn_abc123', description: 'Transaction ID from payment gateway'),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe', nullable: true),
        new OA\Property(
            property: 'payment_data',
            type: 'object',
            nullable: true,
            description: 'Additional payment data from gateway'
        ),
    ]
)]
final class CompleteSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_log_id' => ['required', 'integer', 'exists:payment_logs,id'],
            'transaction_id' => ['required', 'string', 'max:255'],
            'payment_gateway' => ['nullable', 'string', 'max:50'],
            'payment_data' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_log_id.required' => 'Payment log ID is required.',
            'payment_log_id.exists' => 'Payment log not found.',
            'transaction_id.required' => 'Transaction ID is required.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'payment_log_id' => (int) $data['payment_log_id'],
            'transaction_id' => $data['transaction_id'],
            'payment_gateway' => $data['payment_gateway'] ?? null,
            'payment_data' => $data['payment_data'] ?? [],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Cancel Subscription Request DTO
 *
 * Validates data for canceling a subscription.
 */
#[OA\Schema(
    schema: 'CancelSubscriptionRequest',
    title: 'Cancel Subscription Request',
    required: ['reason'],
    properties: [
        new OA\Property(
            property: 'reason',
            type: 'string',
            example: 'No longer need the service',
            maxLength: 1000,
            description: 'Reason for cancellation'
        ),
        new OA\Property(
            property: 'feedback',
            type: 'string',
            nullable: true,
            maxLength: 2000,
            description: 'Additional feedback'
        ),
    ]
)]
final class CancelSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'A reason for cancellation is required.',
            'reason.max' => 'Reason must not exceed 1000 characters.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'reason' => $data['reason'],
            'feedback' => $data['feedback'] ?? null,
        ];
    }
}

```

Now let me update the resources:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\PricePlan
 */
#[OA\Schema(
    schema: 'PricePlanResource',
    title: 'Price Plan Resource',
    description: 'Price plan resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Premium Plan'),
        new OA\Property(property: 'subtitle', type: 'string', example: 'Best for growing businesses', nullable: true),
        new OA\Property(property: 'features_text', type: 'string', example: 'All premium features included', nullable: true),
        new OA\Property(
            property: 'type',
            type: 'integer',
            description: '0 = Monthly, 1 = Yearly, 2 = Lifetime',
            enum: [0, 1, 2],
            example: 0
        ),
        new OA\Property(property: 'type_label', type: 'string', example: 'Monthly'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
        new OA\Property(property: 'formatted_price', type: 'string', example: '$99.99'),
        new OA\Property(property: 'has_trial', type: 'boolean', example: true),
        new OA\Property(property: 'trial_days', type: 'integer', example: 14),
        new OA\Property(property: 'zero_price', type: 'boolean', example: false),
        new OA\Property(
            property: 'permissions',
            properties: [
                new OA\Property(property: 'page', type: 'integer', example: 10, nullable: true),
                new OA\Property(property: 'blog', type: 'integer', example: 50, nullable: true),
                new OA\Property(property: 'product', type: 'integer', example: 100, nullable: true),
                new OA\Property(property: 'portfolio', type: 'integer', example: 20, nullable: true),
                new OA\Property(property: 'storage', type: 'integer', example: 1024, nullable: true, description: 'Storage in MB'),
                new OA\Property(property: 'appointment', type: 'integer', example: 100, nullable: true),
            ],
            type: 'object',
            description: 'Permission limits (-1 for unlimited, null for not included)'
        ),
        new OA\Property(
            property: 'plan_features',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PlanFeatureResource'),
            description: 'List of plan features'
        ),
        new OA\Property(
            property: 'faq',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'answer', type: 'string'),
                ],
                type: 'object'
            ),
            nullable: true
        ),
        new OA\Property(property: 'subscribers_count', type: 'integer', example: 150, description: 'Number of active subscribers'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class PricePlanResource extends JsonResource
{
    /**
     * Plan type labels.
     */
    private const TYPE_LABELS = [
        0 => 'Monthly',
        1 => 'Yearly',
        2 => 'Lifetime',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $faq = null;
        if ($this->faq) {
            $faq = is_string($this->faq) ? @unserialize($this->faq) : $this->faq;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'features_text' => $this->features,
            'type' => $this->type,
            'type_label' => self::TYPE_LABELS[$this->type] ?? 'Unknown',
            'status' => $this->status,
            'price' => (float) $this->price,
            'formatted_price' => $this->formatPrice((float) $this->price),
            'has_trial' => (bool) ($this->has_trial ?? ($this->free_trial > 0)),
            'trial_days' => (int) ($this->trial_days ?? $this->free_trial ?? 0),
            'zero_price' => $this->zero_price,
            'permissions' => [
                'page' => $this->page_permission_feature,
                'blog' => $this->blog_permission_feature,
                'product' => $this->product_permission_feature,
                'portfolio' => $this->portfolio_permission_feature,
                'storage' => $this->storage_permission_feature,
                'appointment' => $this->appointment_permission_feature,
            ],
            'plan_features' => PlanFeatureResource::collection($this->whenLoaded('planFeatures')),
            'faq' => $faq,
            'subscribers_count' => $this->when(
                $this->relationLoaded('paymentLogs'),
                fn () => $this->paymentLogs->where('payment_status', 1)->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Format price with currency symbol.
     */
    private function formatPrice(float $price): string
    {
        // Could be extended to use site settings for currency
        return '$' . number_format($price, 2);
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PricePlanCollection',
    title: 'Price Plan Collection',
    description: 'Collection of price plans with summary',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PricePlanResource')
        ),
        new OA\Property(
            property: 'summary',
            properties: [
                new OA\Property(property: 'total_plans', type: 'integer', example: 5),
                new OA\Property(property: 'active_plans', type: 'integer', example: 4),
                new OA\Property(property: 'has_trial_plans', type: 'integer', example: 2),
            ],
            type: 'object'
        ),
    ]
)]
class PricePlanCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PricePlanResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'summary' => [
                'total_plans' => $this->collection->count(),
                'active_plans' => $this->collection->where('status', true)->count(),
                'has_trial_plans' => $this->collection->filter(
                    fn ($plan) => $plan->has_trial ?? ($plan->free_trial > 0)
                )->count(),
            ],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\PlanFeature
 */
#[OA\Schema(
    schema: 'PlanFeatureResource',
    title: 'Plan Feature Resource',
    description: 'Plan feature resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'plan_id', type: 'integer', example: 1),
        new OA\Property(property: 'feature_name', type: 'string', example: 'eCommerce'),
        new OA\Property(property: 'feature_slug', type: 'string', example: 'ecommerce'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class PlanFeatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'feature_name' => $this->feature_name,
            'feature_slug' => $this->generateSlug($this->feature_name),
            'status' => $this->status,
            'order' => $this->order ?? $this->id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Generate a slug from feature name.
     */
    private function generateSlug(string $name): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim($name)));
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\PaymentLog
 */
#[OA\Schema(
    schema: 'SubscriptionResource',
    title: 'Subscription Resource',
    description: 'User subscription resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(
            property: 'plan',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Premium Plan'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                new OA\Property(property: 'type', type: 'integer', example: 0),
                new OA\Property(property: 'type_label', type: 'string', example: 'Monthly'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'tenant',
            properties: [
                new OA\Property(property: 'id', type: 'string', example: 'mystore'),
                new OA\Property(property: 'domain', type: 'string', example: 'mystore.example.com'),
                new OA\Property(property: 'theme', type: 'string', example: 'default'),
            ],
            type: 'object',
            nullable: true
        ),
        new OA\Property(property: 'price_paid', type: 'number', format: 'float', example: 89.99),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe', nullable: true),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'txn_123456789', nullable: true),
        new OA\Property(property: 'track', type: 'string', example: 'track_abc123', nullable: true),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['pending', 'trial', 'complete', 'cancelled'],
            example: 'complete'
        ),
        new OA\Property(
            property: 'payment_status',
            type: 'integer',
            description: '0 = Pending, 1 = Paid',
            example: 1
        ),
        new OA\Property(property: 'payment_status_label', type: 'string', example: 'Paid'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z', nullable: true),
        new OA\Property(property: 'expire_date', type: 'string', format: 'date-time', example: '2024-12-31T23:59:59.000000Z', nullable: true),
        new OA\Property(property: 'trial_expire_date', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'days_remaining', type: 'integer', example: 30, nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_expired', type: 'boolean', example: false),
        new OA\Property(property: 'is_trial', type: 'boolean', example: false),
        new OA\Property(property: 'is_renew', type: 'boolean', example: false),
        new OA\Property(property: 'can_renew', type: 'boolean', example: true, description: 'Whether subscription can be renewed'),
        new OA\Property(property: 'can_upgrade', type: 'boolean', example: true, description: 'Whether subscription can be upgraded'),
        new OA\Property(property: 'coupon_discount', type: 'number', format: 'float', example: 10.00, nullable: true),
        new OA\Property(property: 'theme', type: 'string', example: 'default', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class SubscriptionResource extends JsonResource
{
    /**
     * Payment status labels.
     */
    private const PAYMENT_STATUS_LABELS = [
        0 => 'Pending',
        1 => 'Paid',
    ];

    /**
     * Plan type labels.
     */
    private const TYPE_LABELS = [
        0 => 'Monthly',
        1 => 'Yearly',
        2 => 'Lifetime',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $package = $this->whenLoaded('package', fn () => $this->package);
        $tenant = $this->whenLoaded('tenant', fn () => $this->tenant);

        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'plan' => $this->when($package instanceof \App\Models\PricePlan, fn () => [
                'id' => $package->id,
                'name' => $package->title,
                'price' => (float) $package->price,
                'type' => $package->type,
                'type_label' => self::TYPE_LABELS[$package->type] ?? 'Unknown',
            ]),
            'tenant' => $this->when($tenant instanceof \App\Models\Tenant, fn () => [
                'id' => $tenant->id,
                'domain' => $tenant->primaryDomain?->domain ?? $tenant->id,
                'theme' => $tenant->theme ?? $this->theme,
            ]),
            'price_paid' => (float) $this->package_price,
            'payment_gateway' => $this->package_gateway,
            'transaction_id' => $this->transaction_id,
            'track' => $this->track,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_status_label' => self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? 'Unknown',
            'start_date' => $this->start_date?->toISOString(),
            'expire_date' => $this->expire_date?->toISOString(),
            'trial_expire_date' => $this->trial_expire_date?->toISOString(),
            'days_remaining' => $this->calculateDaysRemaining(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_trial' => $this->status === 'trial',
            'is_renew' => $this->is_renew,
            'can_renew' => $this->canRenew(),
            'can_upgrade' => $this->canUpgrade(),
            'coupon_discount' => $this->coupon_discount,
            'theme' => $this->theme,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Calculate remaining days until expiration.
     */
    private function calculateDaysRemaining(): ?int
    {
        if ($this->expire_date === null) {
            return null; // Lifetime plan
        }

        if ($this->expire_date->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expire_date, false);
    }

    /**
     * Check if subscription can be renewed.
     */
    private function canRenew(): bool
    {
        // Can renew if:
        // - Payment is complete
        // - Has an expiration date (not lifetime)
        // - Expires within 30 days OR already expired but within grace period
        if ($this->payment_status !== 1 || $this->expire_date === null) {
            return false;
        }

        $daysRemaining = $this->calculateDaysRemaining();

        return $daysRemaining !== null && $daysRemaining <= 30;
    }

    /**
     * Check if subscription can be upgraded.
     */
    private function canUpgrade(): bool
    {
        // Can upgrade if:
        // - Payment is complete
        // - Currently active
        return $this->payment_status === 1 && $this->isActive();
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\Coupon
 */
#[OA\Schema(
    schema: 'CouponResource',
    title: 'Coupon Resource',
    description: 'Coupon resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Summer Sale'),
        new OA\Property(property: 'code', type: 'string', example: 'SUMMER20'),
        new OA\Property(property: 'discount_amount', type: 'number', format: 'float', example: 20.00),
        new OA\Property(
            property: 'discount_type',
            type: 'string',
            enum: ['percentage', 'amount'],
            example: 'percentage'
        ),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'max_use_qty', type: 'integer', example: 100, nullable: true),
        new OA\Property(property: 'expire_date', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_valid', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'discount_amount' => (float) $this->discount_amount,
            'discount_type' => $this->discount_type,
            'status' => $this->status,
            'max_use_qty' => $this->max_use_qty,
            'expire_date' => $this->expire_date?->toISOString(),
            'is_valid' => $this->isValid(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

```

Now let me create the SubscriptionService:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TenantRegisterEvent;
use App\Models\Coupon;
use App\Models\CouponLog;
use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SubscriptionService
 *
 * Handles subscription-related business logic including pricing calculations,
 * subscription initiation, completion, renewal, and cancellation.
 */
final class SubscriptionService
{
    /**
     * Plan type constants.
     */
    public const TYPE_MONTHLY = 0;
    public const TYPE_YEARLY = 1;
    public const TYPE_LIFETIME = 2;

    /**
     * Payment status constants.
     */
    public const PAYMENT_STATUS_PENDING = 0;
    public const PAYMENT_STATUS_COMPLETE = 1;

    /**
     * Calculate the final price for a plan with optional coupon.
     *
     * @return array{
     *     original_price: float,
     *     discount: float,
     *     final_price: float,
     *     coupon_applied: bool,
     *     coupon_code: string|null
     * }
     */
    public function calculatePrice(PricePlan $plan, ?Coupon $coupon = null): array
    {
        $originalPrice = (float) $plan->price;
        $discount = 0.0;
        $couponApplied = false;
        $couponCode = null;

        if ($coupon !== null && $coupon->isValid()) {
            $discount = $coupon->calculateDiscount($originalPrice);
            $couponApplied = true;
            $couponCode = $coupon->code;
        }

        $finalPrice = max(0, $originalPrice - $discount);

        return [
            'original_price' => $originalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'coupon_applied' => $couponApplied,
            'coupon_code' => $couponCode,
        ];
    }

    /**
     * Validate a coupon for a user and plan.
     *
     * @return array{
     *     valid: bool,
     *     message: string,
     *     coupon: Coupon|null,
     *     discount: float
     * }
     */
    public function validateCoupon(string $code, int $planId, int $userId): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon === null) {
            return [
                'valid' => false,
                'message' => 'Invalid coupon code.',
                'coupon' => null,
                'discount' => 0,
            ];
        }

        if (!$coupon->isValid()) {
            return [
                'valid' => false,
                'message' => $coupon->expire_date?->isPast()
                    ? 'Coupon has expired.'
                    : 'Coupon is not active.',
                'coupon' => null,
                'discount' => 0,
            ];
        }

        if ($coupon->hasReachedLimitForUser($userId)) {
            return [
                'valid' => false,
                'message' => 'You have reached the maximum usage limit for this coupon.',
                'coupon' => null,
                'discount' => 0,
            ];
        }

        $plan = PricePlan::find($planId);
        if ($plan === null) {
            return [
                'valid' => false,
                'message' => 'Plan not found.',
                'coupon' => null,
                'discount' => 0,
            ];
        }

        $discount = $coupon->calculateDiscount((float) $plan->price);

        return [
            'valid' => true,
            'message' => 'Coupon is valid.',
            'coupon' => $coupon,
            'discount' => $discount,
        ];
    }

    /**
     * Initiate a new subscription.
     *
     * Creates a pending payment log that can be completed after payment.
     */
    public function initiateSubscription(
        User $user,
        PricePlan $plan,
        string $subdomain,
        ?string $theme = null,
        ?Coupon $coupon = null,
        ?string $paymentGateway = null,
        bool $isTrial = false
    ): PaymentLog {
        $pricing = $this->calculatePrice($plan, $coupon);

        return DB::transaction(function () use ($user, $plan, $subdomain, $theme, $coupon, $paymentGateway, $isTrial, $pricing) {
            $startDate = Carbon::now();
            $expireDate = $this->calculateExpireDate($plan, $startDate);

            $paymentLog = PaymentLog::create([
                'email' => $user->email,
                'name' => $user->name,
                'package_name' => $plan->title,
                'package_price' => $pricing['final_price'],
                'package_gateway' => $paymentGateway,
                'package_id' => $plan->id,
                'user_id' => $user->id,
                'tenant_id' => $subdomain,
                'status' => $isTrial ? 'trial' : 'pending',
                'payment_status' => $isTrial || $pricing['final_price'] == 0 ? self::PAYMENT_STATUS_COMPLETE : self::PAYMENT_STATUS_PENDING,
                'track' => $this->generateTrackCode(),
                'start_date' => $startDate,
                'expire_date' => $expireDate,
                'trial_expire_date' => $isTrial && $plan->trial_days > 0
                    ? $startDate->copy()->addDays($plan->trial_days)
                    : null,
                'theme' => $theme,
                'is_renew' => false,
                'coupon_id' => $coupon?->id,
                'coupon_discount' => $pricing['discount'],
                'unique_key' => Str::uuid()->toString(),
            ]);

            // Log coupon usage
            if ($coupon !== null) {
                CouponLog::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'payment_log_id' => $paymentLog->id,
                    'discount_amount' => $pricing['discount'],
                ]);
            }

            return $paymentLog;
        });
    }

    /**
     * Complete a subscription after successful payment.
     */
    public function completeSubscription(
        PaymentLog $paymentLog,
        string $transactionId,
        ?string $paymentGateway = null
    ): Tenant {
        return DB::transaction(function () use ($paymentLog, $transactionId, $paymentGateway) {
            // Update payment log
            $paymentLog->update([
                'transaction_id' => $transactionId,
                'status' => $paymentLog->status === 'trial' ? 'trial' : 'complete',
                'payment_status' => self::PAYMENT_STATUS_COMPLETE,
                'package_gateway' => $paymentGateway ?? $paymentLog->package_gateway,
            ]);

            // Get or create tenant
            $tenant = Tenant::find($paymentLog->tenant_id);

            if ($tenant === null) {
                // Create new tenant
                $tenant = $this->createTenant($paymentLog);
            } else {
                // Update existing tenant
                $this->updateTenant($tenant, $paymentLog);
            }

            // Update user
            $user = User::find($paymentLog->user_id);
            if ($user !== null) {
                $user->update(['has_subdomain' => true]);
            }

            return $tenant;
        });
    }

    /**
     * Renew an existing subscription.
     */
    public function renewSubscription(PaymentLog $existingLog): PaymentLog
    {
        $plan = PricePlan::find($existingLog->package_id);
        if ($plan === null) {
            throw new \InvalidArgumentException('Plan not found for renewal.');
        }

        $user = User::find($existingLog->user_id);
        if ($user === null) {
            throw new \InvalidArgumentException('User not found for renewal.');
        }

        return DB::transaction(function () use ($existingLog, $plan, $user) {
            // Calculate new dates based on current expiration
            $startDate = $existingLog->expire_date?->isFuture()
                ? $existingLog->expire_date
                : Carbon::now();

            $expireDate = $this->calculateExpireDate($plan, $startDate);

            $newLog = PaymentLog::create([
                'email' => $user->email,
                'name' => $user->name,
                'package_name' => $plan->title,
                'package_price' => $plan->price,
                'package_gateway' => $existingLog->package_gateway,
                'package_id' => $plan->id,
                'user_id' => $user->id,
                'tenant_id' => $existingLog->tenant_id,
                'status' => 'pending',
                'payment_status' => self::PAYMENT_STATUS_PENDING,
                'track' => $this->generateTrackCode(),
                'start_date' => $startDate,
                'expire_date' => $expireDate,
                'theme' => $existingLog->theme,
                'is_renew' => true,
                'renew_status' => true,
                'unique_key' => Str::uuid()->toString(),
            ]);

            return $newLog;
        });
    }

    /**
     * Upgrade to a new plan.
     *
     * @return array{
     *     payment_log: PaymentLog,
     *     prorated_amount: float,
     *     credit_remaining: float
     * }
     */
    public function upgradeSubscription(
        PaymentLog $currentLog,
        PricePlan $newPlan,
        ?Coupon $coupon = null
    ): array {
        $user = User::find($currentLog->user_id);
        if ($user === null) {
            throw new \InvalidArgumentException('User not found for upgrade.');
        }

        $currentPlan = PricePlan::find($currentLog->package_id);
        if ($currentPlan === null) {
            throw new \InvalidArgumentException('Current plan not found.');
        }

        // Calculate prorated credit
        $creditRemaining = $this->calculateProratedCredit($currentLog, $currentPlan);
        $newPricing = $this->calculatePrice($newPlan, $coupon);

        $proratedAmount = max(0, $newPricing['final_price'] - $creditRemaining);

        return DB::transaction(function () use ($user, $newPlan, $currentLog, $coupon, $newPricing, $proratedAmount, $creditRemaining) {
            $startDate = Carbon::now();
            $expireDate = $this->calculateExpireDate($newPlan, $startDate);

            $paymentLog = PaymentLog::create([
                'email' => $user->email,
                'name' => $user->name,
                'package_name' => $newPlan->title,
                'package_price' => $proratedAmount,
                'package_gateway' => $currentLog->package_gateway,
                'package_id' => $newPlan->id,
                'user_id' => $user->id,
                'tenant_id' => $currentLog->tenant_id,
                'status' => $proratedAmount == 0 ? 'complete' : 'pending',
                'payment_status' => $proratedAmount == 0 ? self::PAYMENT_STATUS_COMPLETE : self::PAYMENT_STATUS_PENDING,
                'track' => $this->generateTrackCode(),
                'start_date' => $startDate,
                'expire_date' => $expireDate,
                'theme' => $currentLog->theme,
                'is_renew' => false,
                'coupon_id' => $coupon?->id,
                'coupon_discount' => $newPricing['discount'],
                'unique_key' => Str::uuid()->toString(),
            ]);

            // Log coupon usage
            if ($coupon !== null) {
                CouponLog::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'payment_log_id' => $paymentLog->id,
                    'discount_amount' => $newPricing['discount'],
                ]);
            }

            // If no payment needed, complete immediately
            if ($proratedAmount == 0) {
                $tenant = Tenant::find($currentLog->tenant_id);
                if ($tenant !== null) {
                    $this->updateTenant($tenant, $paymentLog);
                }
            }

            return [
                'payment_log' => $paymentLog,
                'prorated_amount' => $proratedAmount,
                'credit_remaining' => $creditRemaining,
            ];
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(PaymentLog $paymentLog, string $reason): bool
    {
        return DB::transaction(function () use ($paymentLog, $reason) {
            $paymentLog->update([
                'status' => 'cancelled',
                'custom_fields' => array_merge($paymentLog->custom_fields ?? [], [
                    'cancellation_reason' => $reason,
                    'cancelled_at' => Carbon::now()->toISOString(),
                ]),
            ]);

            return true;
        });
    }

    /**
     * Get user's current active subscription.
     */
    public function getCurrentSubscription(User $user): ?PaymentLog
    {
        return PaymentLog::where('user_id', $user->id)
            ->where('payment_status', self::PAYMENT_STATUS_COMPLETE)
            ->where(function ($query) {
                $query->whereNull('expire_date')
                    ->orWhere('expire_date', '>', Carbon::now());
            })
            ->whereNotIn('status', ['cancelled'])
            ->with(['package', 'tenant'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Get user's subscription history.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getSubscriptionHistory(User $user, int $perPage = 15)
    {
        return PaymentLog::where('user_id', $user->id)
            ->with(['package', 'tenant'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Calculate expiration date based on plan type.
     */
    private function calculateExpireDate(PricePlan $plan, Carbon $startDate): ?Carbon
    {
        return match ($plan->type) {
            self::TYPE_MONTHLY => $startDate->copy()->addMonth(),
            self::TYPE_YEARLY => $startDate->copy()->addYear(),
            self::TYPE_LIFETIME => null, // No expiration
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * Calculate prorated credit for upgrade.
     */
    private function calculateProratedCredit(PaymentLog $currentLog, PricePlan $currentPlan): float
    {
        if ($currentLog->expire_date === null) {
            return 0; // Lifetime plans don't get prorated
        }

        if ($currentLog->expire_date->isPast()) {
            return 0; // Already expired
        }

        $totalDays = match ($currentPlan->type) {
            self::TYPE_MONTHLY => 30,
            self::TYPE_YEARLY => 365,
            default => 30,
        };

        $daysRemaining = (int) Carbon::now()->diffInDays($currentLog->expire_date, false);
        $daysRemaining = max(0, $daysRemaining);

        $dailyRate = (float) $currentLog->package_price / $totalDays;

        return round($dailyRate * $daysRemaining, 2);
    }

    /**
     * Generate a unique tracking code.
     */
    private function generateTrackCode(): string
    {
        return 'TRK' . strtoupper(Str::random(10)) . time();
    }

    /**
     * Create a new tenant from payment log.
     */
    private function createTenant(PaymentLog $paymentLog): Tenant
    {
        $tenant = Tenant::create([
            'id' => $paymentLog->tenant_id,
            'user_id' => $paymentLog->user_id,
            'theme' => $paymentLog->theme,
        ]);

        // Create domain
        $tenant->domains()->create([
            'domain' => $paymentLog->tenant_id,
        ]);

        // Fire tenant creation event (for database setup, etc.)
        $user = User::find($paymentLog->user_id);
        if ($user !== null) {
            event(new TenantRegisterEvent($user, $tenant->id, $paymentLog->theme));
        }

        return $tenant;
    }

    /**
     * Update existing tenant with new subscription data.
     */
    private function updateTenant(Tenant $tenant, PaymentLog $paymentLog): void
    {
        DB::table('tenants')->where('id', $tenant->id)->update([
            'start_date' => $paymentLog->start_date,
            'expire_date' => $paymentLog->expire_date,
            'theme_slug' => $paymentLog->theme,
            'user_id' => $paymentLog->user_id,
        ]);
    }
}

```

Now let me create the PricePlanService and then the controllers:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanFeature;
use App\Models\PricePlan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PricePlanService
 *
 * Handles price plan management operations.
 */
final class PricePlanService
{
    /**
     * Get all price plans for admin with pagination.
     *
     * @param array<string, mixed> $filters
     */
    public function getPlansForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = PricePlan::with('planFeatures')
            ->orderByDesc('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get all active price plans for public display.
     */
    public function getActivePlans(): Collection
    {
        return PricePlan::with('planFeatures')
            ->where('status', true)
            ->orderBy('price')
            ->get();
    }

    /**
     * Get a single plan by ID.
     */
    public function getPlanById(int $id): ?PricePlan
    {
        return PricePlan::with('planFeatures')->find($id);
    }

    /**
     * Get a single plan by slug (using title as slug).
     */
    public function getPlanBySlug(string $slug): ?PricePlan
    {
        // Convert slug to search pattern
        $search = str_replace('-', ' ', $slug);

        return PricePlan::with('planFeatures')
            ->where('status', true)
            ->where('title', 'like', "%{$search}%")
            ->first();
    }

    /**
     * Create a new price plan.
     *
     * @param array<string, mixed> $data
     */
    public function createPlan(array $data): PricePlan
    {
        return DB::transaction(function () use ($data) {
            $features = $data['features'] ?? [];
            unset($data['features']);

            $plan = PricePlan::create($data);

            if (!empty($features)) {
                $this->syncFeatures($plan, $features);
            }

            return $plan->load('planFeatures');
        });
    }

    /**
     * Update an existing price plan.
     *
     * @param array<string, mixed> $data
     */
    public function updatePlan(PricePlan $plan, array $data): PricePlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $features = $data['features'] ?? null;
            unset($data['features']);

            $plan->update($data);

            if ($features !== null) {
                $this->syncFeatures($plan, $features);
            }

            return $plan->load('planFeatures');
        });
    }

    /**
     * Delete a price plan.
     */
    public function deletePlan(PricePlan $plan): bool
    {
        return DB::transaction(function () use ($plan) {
            // Delete associated features
            $plan->planFeatures()->delete();

            return $plan->delete();
        });
    }

    /**
     * Toggle plan status.
     */
    public function toggleStatus(PricePlan $plan): PricePlan
    {
        $plan->update([
            'status' => !$plan->status,
        ]);

        return $plan;
    }

    /**
     * Reorder plan features.
     *
     * @param array<int, int> $featureIds
     */
    public function reorderFeatures(PricePlan $plan, array $featureIds): PricePlan
    {
        return DB::transaction(function () use ($plan, $featureIds) {
            foreach ($featureIds as $order => $featureId) {
                PlanFeature::where('id', $featureId)
                    ->where('plan_id', $plan->id)
                    ->update(['order' => $order + 1]);
            }

            return $plan->load('planFeatures');
        });
    }

    /**
     * Get comparison matrix for all active plans.
     *
     * @return array<string, mixed>
     */
    public function getComparisonMatrix(): array
    {
        $plans = $this->getActivePlans();

        // Collect all unique features
        $allFeatures = [];
        foreach ($plans as $plan) {
            foreach ($plan->planFeatures as $feature) {
                $allFeatures[$feature->feature_name] = true;
            }
        }

        $featureNames = array_keys($allFeatures);

        // Build comparison matrix
        $matrix = [];
        foreach ($plans as $plan) {
            $planFeatures = $plan->planFeatures->pluck('feature_name')->toArray();

            $matrix[] = [
                'id' => $plan->id,
                'title' => $plan->title,
                'subtitle' => $plan->subtitle,
                'price' => (float) $plan->price,
                'type' => $plan->type,
                'type_label' => $this->getTypeLabel($plan->type),
                'has_trial' => (bool) ($plan->has_trial ?? ($plan->free_trial > 0)),
                'trial_days' => (int) ($plan->trial_days ?? $plan->free_trial ?? 0),
                'permissions' => [
                    'page' => $plan->page_permission_feature,
                    'blog' => $plan->blog_permission_feature,
                    'product' => $plan->product_permission_feature,
                    'portfolio' => $plan->portfolio_permission_feature,
                    'storage' => $plan->storage_permission_feature,
                    'appointment' => $plan->appointment_permission_feature,
                ],
                'features' => array_map(
                    fn ($name) => [
                        'name' => $name,
                        'included' => in_array($name, $planFeatures),
                    ],
                    $featureNames
                ),
            ];
        }

        return [
            'plans' => $matrix,
            'feature_names' => $featureNames,
        ];
    }

    /**
     * Sync features for a plan.
     *
     * @param array<int, array<string, mixed>> $features
     */
    private function syncFeatures(PricePlan $plan, array $features): void
    {
        // Get existing feature IDs
        $existingIds = $plan->planFeatures()->pluck('id')->toArray();
        $newIds = [];

        foreach ($features as $index => $feature) {
            $featureId = $feature['id'] ?? null;

            if ($featureId !== null && in_array($featureId, $existingIds)) {
                // Update existing
                PlanFeature::where('id', $featureId)->update([
                    'feature_name' => $feature['feature_name'],
                    'status' => (bool) ($feature['status'] ?? true),
                    'order' => $index + 1,
                ]);
                $newIds[] = $featureId;
            } else {
                // Create new
                $newFeature = PlanFeature::create([
                    'plan_id' => $plan->id,
                    'feature_name' => $feature['feature_name'],
                    'status' => (bool) ($feature['status'] ?? true),
                    'order' => $index + 1,
                ]);
                $newIds[] = $newFeature->id;
            }
        }

        // Delete removed features
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            PlanFeature::whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Get type label.
     */
    private function getTypeLabel(int $type): string
    {
        return match ($type) {
            0 => 'Monthly',
            1 => 'Yearly',
            2 => 'Lifetime',
            default => 'Unknown',
        };
    }
}

```

Now let me create the Admin PricePlanController:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\PricePlan\ReorderFeaturesRequest;
use App\Http\Requests\PricePlan\StorePricePlanRequest;
use App\Http\Requests\PricePlan\UpdatePricePlanRequest;
use App\Http\Resources\PricePlanCollection;
use App\Http\Resources\PricePlanResource;
use App\Services\PricePlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Admin Price Plan Controller
 *
 * Handles price plan management operations by administrators.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Price Plan Management (Admin)',
    description: 'Admin endpoints to manage price plans and features (Guard: api_admin)'
)]
final class PricePlanController extends BaseApiController
{
    public function __construct(
        private readonly PricePlanService $pricePlanService,
    ) {}

    /**
     * Get paginated list of all price plans.
     */
    #[OA\Get(
        path: '/api/v1/admin/price-plans',
        summary: 'List all price plans',
        description: 'Get paginated list of all price plans with features (admin view)',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'Search by title or subtitle',
        schema: new OA\Schema(type: 'string', example: 'premium')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'Filter by status (true/false)',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'type',
        in: 'query',
        required: false,
        description: 'Filter by type (0=Monthly, 1=Yearly, 2=Lifetime)',
        schema: new OA\Schema(type: 'integer', enum: [0, 1, 2])
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
        description: 'Price plans retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plans retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PricePlanResource')
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
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->has('status') ? $request->boolean('status') : null,
            'type' => $request->has('type') ? (int) $request->query('type') : null,
            'per_page' => min((int) $request->query('per_page', $this->perPage), 100),
        ];

        // Remove null values
        $filters = array_filter($filters, fn ($v) => $v !== null);

        $plans = $this->pricePlanService->getPlansForAdmin($filters);

        return $this->paginated(
            $plans,
            PricePlanResource::class,
            'Price plans retrieved successfully'
        );
    }

    /**
     * Create a new price plan.
     */
    #[OA\Post(
        path: '/api/v1/admin/price-plans',
        summary: 'Create price plan',
        description: 'Create a new price plan with features',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StorePricePlanRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Price plan created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
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
    public function store(StorePricePlanRequest $request): JsonResponse
    {
        try {
            $plan = $this->pricePlanService->createPlan($request->validatedData());

            return $this->created(
                new PricePlanResource($plan),
                'Price plan created successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create price plan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a single price plan.
     */
    #[OA\Get(
        path: '/api/v1/admin/price-plans/{id}',
        summary: 'Get price plan details',
        description: 'Retrieve details of a specific price plan with all features',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plan retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
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
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
            ]
        )
    )]
    public function show(int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        return $this->success(
            new PricePlanResource($plan),
            'Price plan retrieved successfully'
        );
    }

    /**
     * Update a price plan.
     */
    #[OA\Put(
        path: '/api/v1/admin/price-plans/{id}',
        summary: 'Update price plan',
        description: 'Update an existing price plan',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdatePricePlanRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plan updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
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
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
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
    public function update(UpdatePricePlanRequest $request, int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        try {
            $updatedPlan = $this->pricePlanService->updatePlan($plan, $request->validatedData());

            return $this->success(
                new PricePlanResource($updatedPlan),
                'Price plan updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update price plan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a price plan.
     */
    #[OA\Delete(
        path: '/api/v1/admin/price-plans/{id}',
        summary: 'Delete price plan',
        description: 'Delete a price plan and all its features',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plan deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan deleted successfully'),
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
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
            ]
        )
    )]
    public function destroy(int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        try {
            $this->pricePlanService->deletePlan($plan);

            return $this->success(null, 'Price plan deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete price plan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Toggle price plan status.
     */
    #[OA\Patch(
        path: '/api/v1/admin/price-plans/{id}/toggle-status',
        summary: 'Toggle plan status',
        description: 'Toggle the active/inactive status of a price plan',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plan status toggled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan status updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
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
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
            ]
        )
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        try {
            $updatedPlan = $this->pricePlanService->toggleStatus($plan);

            return $this->success(
                new PricePlanResource($updatedPlan),
                'Price plan status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to toggle price plan status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reorder plan features.
     */
    #[OA\Patch(
        path: '/api/v1/admin/price-plans/{id}/reorder-features',
        summary: 'Reorder plan features',
        description: 'Reorder the features of a price plan',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ReorderFeaturesRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Features reordered successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Features reordered successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
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
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
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
    public function reorderFeatures(ReorderFeaturesRequest $request, int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        try {
            $updatedPlan = $this->pricePlanService->reorderFeatures($plan, $request->getFeatureIds());

            return $this->success(
                new PricePlanResource($updatedPlan),
                'Features reordered successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to reorder features: ' . $e->getMessage(), 500);
        }
    }
}

```

Now let me create the public PlanController:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\PricePlanCollection;
use App\Http\Resources\PricePlanResource;
use App\Services\PricePlanService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

/**
 * Public Plan Controller
 *
 * Handles public-facing price plan operations.
 * These endpoints are accessible without authentication or with user authentication.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'Price Plans (Public)',
    description: 'Public endpoints for viewing pricing plans and comparing features'
)]
final class PlanController extends BaseApiController
{
    public function __construct(
        private readonly PricePlanService $pricePlanService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Get all active price plans.
     */
    #[OA\Get(
        path: '/api/v1/plans',
        summary: 'List active plans',
        description: 'Get all active price plans with features for public display',
        tags: ['Price Plans (Public)']
    )]
    #[OA\Response(
        response: 200,
        description: 'Active plans retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Plans retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/PricePlanResource')
                        ),
                        new OA\Property(
                            property: 'summary',
                            properties: [
                                new OA\Property(property: 'total_plans', type: 'integer', example: 5),
                                new OA\Property(property: 'active_plans', type: 'integer', example: 4),
                                new OA\Property(property: 'has_trial_plans', type: 'integer', example: 2),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function index(): JsonResponse
    {
        $plans = $this->pricePlanService->getActivePlans();

        return $this->success(
            new PricePlanCollection($plans),
            'Plans retrieved successfully'
        );
    }

    /**
     * Get plan details by slug.
     */
    #[OA\Get(
        path: '/api/v1/plans/{slug}',
        summary: 'Get plan details',
        description: 'Get details of a specific plan by slug for purchase page',
        tags: ['Price Plans (Public)']
    )]
    #[OA\Parameter(
        name: 'slug',
        in: 'path',
        required: true,
        description: 'Plan slug (URL-friendly title)',
        schema: new OA\Schema(type: 'string', example: 'premium-plan')
    )]
    #[OA\Response(
        response: 200,
        description: 'Plan retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Plan retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Plan not found'),
            ]
        )
    )]
    public function show(string $slug): JsonResponse
    {
        // Try to find by ID first (for numeric slugs)
        if (is_numeric($slug)) {
            $plan = $this->pricePlanService->getPlanById((int) $slug);
            if ($plan !== null && !$plan->status) {
                $plan = null; // Don't show inactive plans
            }
        } else {
            $plan = $this->pricePlanService->getPlanBySlug($slug);
        }

        if ($plan === null) {
            return $this->notFound('Plan not found');
        }

        return $this->success(
            new PricePlanResource($plan),
            'Plan retrieved successfully'
        );
    }

    /**
     * Get comparison matrix of all active plans.
     */
    #[OA\Get(
        path: '/api/v1/plans/compare',
        summary: 'Compare all plans',
        description: 'Get a comparison matrix of all active plans with feature availability',
        tags: ['Price Plans (Public)']
    )]
    #[OA\Response(
        response: 200,
        description: 'Plan comparison retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Plan comparison retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'plans',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Premium Plan'),
                                    new OA\Property(property: 'subtitle', type: 'string', example: 'Best for businesses'),
                                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                                    new OA\Property(property: 'type', type: 'integer', example: 0),
                                    new OA\Property(property: 'type_label', type: 'string', example: 'Monthly'),
                                    new OA\Property(property: 'has_trial', type: 'boolean', example: true),
                                    new OA\Property(property: 'trial_days', type: 'integer', example: 14),
                                    new OA\Property(property: 'permissions', type: 'object'),
                                    new OA\Property(
                                        property: 'features',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'name', type: 'string', example: 'eCommerce'),
                                                new OA\Property(property: 'included', type: 'boolean', example: true),
                                            ],
                                            type: 'object'
                                        )
                                    ),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'feature_names',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['eCommerce', 'Blog', 'Analytics']
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function compare(): JsonResponse
    {
        $comparison = $this->pricePlanService->getComparisonMatrix();

        return $this->success($comparison, 'Plan comparison retrieved successfully');
    }

    /**
     * Validate a coupon code.
     */
    #[OA\Post(
        path: '/api/v1/plans/check-coupon',
        summary: 'Validate coupon',
        description: 'Check if a coupon code is valid for a plan',
        security: [['sanctum_user' => []]],
        tags: ['Price Plans (Public)']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['code', 'plan_id'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'SAVE20', description: 'Coupon code'),
                new OA\Property(property: 'plan_id', type: 'integer', example: 1, description: 'Plan ID'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Coupon validation result',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Coupon is valid'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean', example: true),
                        new OA\Property(property: 'discount', type: 'number', format: 'float', example: 20.00),
                        new OA\Property(property: 'discount_type', type: 'string', example: 'percentage'),
                        new OA\Property(property: 'original_price', type: 'number', format: 'float', example: 99.99),
                        new OA\Property(property: 'final_price', type: 'number', format: 'float', example: 79.99),
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
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function checkCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'plan_id' => ['required', 'integer', 'exists:price_plans,id'],
        ]);

        $userId = Auth::guard('api_user')->id();

        if ($userId === null) {
            return $this->unauthorized('Please log in to check coupon');
        }

        $result = $this->subscriptionService->validateCoupon(
            $request->input('code'),
            (int) $request->input('plan_id'),
            (int) $userId
        );

        if (!$result['valid']) {
            return $this->success([
                'valid' => false,
                'message' => $result['message'],
                'discount' => 0,
            ], $result['message']);
        }

        $plan = $this->pricePlanService->getPlanById((int) $request->input('plan_id'));

        return $this->success([
            'valid' => true,
            'discount' => $result['discount'],
            'discount_type' => $result['coupon']->discount_type,
            'discount_amount' => (float) $result['coupon']->discount_amount,
            'original_price' => (float) $plan->price,
            'final_price' => max(0, (float) $plan->price - $result['discount']),
        ], 'Coupon is valid');
    }
}

```

Now let me create the SubscriptionController:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Subscription\CancelSubscriptionRequest;
use App\Http\Requests\Subscription\CompleteSubscriptionRequest;
use App\Http\Requests\Subscription\InitiateSubscriptionRequest;
use App\Http\Resources\PaymentLogResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Coupon;
use App\Models\PaymentGateway;
use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\User;
use App\Services\PricePlanService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

/**
 * Subscription Controller
 *
 * Handles user subscription operations including initiation, completion,
 * renewal, upgrade, and cancellation.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'Subscriptions',
    description: 'User subscription management endpoints (Guard: api_user)'
)]
final class SubscriptionController extends BaseApiController
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly PricePlanService $pricePlanService,
    ) {}

    /**
     * Get authenticated user.
     */
    private function getUser(): ?User
    {
        return Auth::guard('api_user')->user();
    }

    /**
     * Get user's current active subscription.
     */
    #[OA\Get(
        path: '/api/v1/subscriptions/current',
        summary: 'Get current subscription',
        description: 'Get the authenticated user\'s currently active subscription',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\Response(
        response: 200,
        description: 'Current subscription retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Current subscription retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubscriptionResource', nullable: true),
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
    public function current(): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $subscription = $this->subscriptionService->getCurrentSubscription($user);

        if ($subscription === null) {
            return $this->success(null, 'No active subscription found');
        }

        return $this->success(
            new SubscriptionResource($subscription),
            'Current subscription retrieved'
        );
    }

    /**
     * Get user's subscription history.
     */
    #[OA\Get(
        path: '/api/v1/subscriptions/history',
        summary: 'Get subscription history',
        description: 'Get paginated subscription history for the authenticated user',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
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
        description: 'Subscription history retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription history retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SubscriptionResource')
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
    public function history(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $perPage = min((int) $request->query('per_page', $this->perPage), 100);
        $history = $this->subscriptionService->getSubscriptionHistory($user, $perPage);

        return $this->paginated(
            $history,
            SubscriptionResource::class,
            'Subscription history retrieved'
        );
    }

    /**
     * Initiate a new subscription.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/initiate',
        summary: 'Initiate subscription',
        description: 'Start the subscription flow. Returns payment options and a pending payment log.',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/InitiateSubscriptionRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Subscription initiated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription initiated'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'payment_log', ref: '#/components/schemas/PaymentLogResource'),
                        new OA\Property(
                            property: 'pricing',
                            properties: [
                                new OA\Property(property: 'original_price', type: 'number', format: 'float', example: 99.99),
                                new OA\Property(property: 'discount', type: 'number', format: 'float', example: 10.00),
                                new OA\Property(property: 'final_price', type: 'number', format: 'float', example: 89.99),
                                new OA\Property(property: 'coupon_applied', type: 'boolean', example: true),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'payment_gateways',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Stripe'),
                                    new OA\Property(property: 'slug', type: 'string', example: 'stripe'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'requires_payment', type: 'boolean', example: true),
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
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function initiate(InitiateSubscriptionRequest $request): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $data = $request->validatedData();

        // Get the plan
        $plan = PricePlan::find($data['plan_id']);

        if ($plan === null || !$plan->status) {
            return $this->notFound('Plan not found or inactive');
        }

        // Validate coupon if provided
        $coupon = null;
        if (!empty($data['coupon_code'])) {
            $couponResult = $this->subscriptionService->validateCoupon(
                $data['coupon_code'],
                $data['plan_id'],
                $user->id
            );

            if (!$couponResult['valid']) {
                return $this->validationError(['coupon_code' => [$couponResult['message']]]);
            }

            $coupon = $couponResult['coupon'];
        }

        // Check if user already has an active subscription for this subdomain
        $existingLog = PaymentLog::where('user_id', $user->id)
            ->where('tenant_id', $data['subdomain'])
            ->where('payment_status', 1)
            ->first();

        if ($existingLog !== null) {
            return $this->error('You already have an active subscription for this subdomain', 409);
        }

        try {
            $paymentLog = $this->subscriptionService->initiateSubscription(
                $user,
                $plan,
                $data['subdomain'],
                $data['theme'],
                $coupon,
                $data['payment_gateway'],
                $data['is_trial']
            );

            // Get pricing information
            $pricing = $this->subscriptionService->calculatePrice($plan, $coupon);

            // Get available payment gateways
            $gateways = PaymentGateway::where('status', true)
                ->select('id', 'name')
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'slug' => strtolower(str_replace(' ', '_', $g->name)),
                ]);

            $requiresPayment = $pricing['final_price'] > 0 && !$data['is_trial'];

            // If it's a trial or free plan, complete immediately
            if (!$requiresPayment) {
                $this->subscriptionService->completeSubscription(
                    $paymentLog,
                    'FREE_OR_TRIAL_' . $paymentLog->track,
                    'free'
                );
                $paymentLog->refresh();
            }

            return $this->created([
                'payment_log' => new PaymentLogResource($paymentLog->load(['package', 'tenant'])),
                'pricing' => $pricing,
                'payment_gateways' => $gateways,
                'requires_payment' => $requiresPayment,
            ], $requiresPayment ? 'Subscription initiated. Complete payment to activate.' : 'Subscription created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to initiate subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Complete a subscription payment.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/complete',
        summary: 'Complete subscription',
        description: 'Complete the subscription after successful payment',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CompleteSubscriptionRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Subscription completed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription completed successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'subscription', ref: '#/components/schemas/SubscriptionResource'),
                        new OA\Property(property: 'tenant_url', type: 'string', example: 'https://mystore.example.com'),
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
        description: 'Payment log not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Payment log not found'),
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
    public function complete(CompleteSubscriptionRequest $request): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $data = $request->validatedData();

        $paymentLog = PaymentLog::where('id', $data['payment_log_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($paymentLog === null) {
            return $this->notFound('Payment log not found');
        }

        if ($paymentLog->payment_status === 1) {
            return $this->error('This subscription is already completed', 409);
        }

        try {
            $tenant = $this->subscriptionService->completeSubscription(
                $paymentLog,
                $data['transaction_id'],
                $data['payment_gateway']
            );

            $paymentLog->refresh();

            // Build tenant URL
            $domain = $tenant->primaryDomain?->domain ?? $tenant->id;
            $tenantUrl = 'https://' . $domain . '.' . config('app.domain', 'example.com');

            return $this->success([
                'subscription' => new SubscriptionResource($paymentLog->load(['package', 'tenant'])),
                'tenant_url' => $tenantUrl,
            ], 'Subscription completed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to complete subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a subscription.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/{subscriptionId}/cancel',
        summary: 'Cancel subscription',
        description: 'Cancel an active subscription',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\Parameter(
        name: 'subscriptionId',
        in: 'path',
        required: true,
        description: 'Subscription (Payment Log) ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CancelSubscriptionRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Subscription cancelled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription cancelled successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubscriptionResource'),
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
        description: 'Subscription not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription not found'),
            ]
        )
    )]
    public function cancel(CancelSubscriptionRequest $request, int $subscriptionId): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $paymentLog = PaymentLog::where('id', $subscriptionId)
            ->where('user_id', $user->id)
            ->first();

        if ($paymentLog === null) {
            return $this->notFound('Subscription not found');
        }

        if ($paymentLog->status === 'cancelled') {
            return $this->error('This subscription is already cancelled', 409);
        }

        try {
            $data = $request->validatedData();
            $this->subscriptionService->cancelSubscription($paymentLog, $data['reason']);
            $paymentLog->refresh();

            return $this->success(
                new SubscriptionResource($paymentLog->load(['package', 'tenant'])),
                'Subscription cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Renew an existing subscription.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/{subscriptionId}/renew',
        summary: 'Renew subscription',
        description: 'Create a renewal payment for an existing subscription',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\Parameter(
        name: 'subscriptionId',
        in: 'path',
        required: true,
        description: 'Subscription (Payment Log) ID to renew',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 201,
        description: 'Renewal initiated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Renewal initiated'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'payment_log', ref: '#/components/schemas/PaymentLogResource'),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 99.99),
                        new OA\Property(
                            property: 'payment_gateways',
                            type: 'array',
                            items: new OA\Items(type: 'object')
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
        description: 'Subscription not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription not found'),
            ]
        )
    )]
    public function renew(int $subscriptionId): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $paymentLog = PaymentLog::where('id', $subscriptionId)
            ->where('user_id', $user->id)
            ->where('payment_status', 1)
            ->first();

        if ($paymentLog === null) {
            return $this->notFound('Subscription not found');
        }

        try {
            $renewalLog = $this->subscriptionService->renewSubscription($paymentLog);

            // Get available payment gateways
            $gateways = PaymentGateway::where('status', true)
                ->select('id', 'name')
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'slug' => strtolower(str_replace(' ', '_', $g->name)),
                ]);

            return $this->created([
                'payment_log' => new PaymentLogResource($renewalLog->load(['package', 'tenant'])),
                'amount' => (float) $renewalLog->package_price,
                'payment_gateways' => $gateways,
            ], 'Renewal initiated. Complete payment to extend subscription.');
        } catch (\Exception $e) {
            return $this->error('Failed to initiate renewal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Upgrade to a new plan.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/upgrade',
        summary: 'Upgrade subscription',
        description: 'Upgrade current subscription to a new plan (prorated pricing)',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['plan_id'],
            properties: [
                new OA\Property(property: 'plan_id', type: 'integer', example: 2, description: 'New plan ID'),
                new OA\Property(property: 'coupon_code', type: 'string', example: 'UPGRADE20', nullable: true),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Upgrade initiated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Upgrade initiated'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'payment_log', ref: '#/components/schemas/PaymentLogResource'),
                        new OA\Property(property: 'prorated_amount', type: 'number', format: 'float', example: 50.00),
                        new OA\Property(property: 'credit_remaining', type: 'number', format: 'float', example: 30.00),
                        new OA\Property(property: 'requires_payment', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'payment_gateways',
                            type: 'array',
                            items: new OA\Items(type: 'object')
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
        description: 'No active subscription or plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'No active subscription found'),
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
    public function upgrade(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:price_plans,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        // Get current active subscription
        $currentSubscription = $this->subscriptionService->getCurrentSubscription($user);

        if ($currentSubscription === null) {
            return $this->notFound('No active subscription found');
        }

        // Get new plan
        $newPlan = PricePlan::find($request->input('plan_id'));

        if ($newPlan === null || !$newPlan->status) {
            return $this->notFound('Plan not found or inactive');
        }

        // Check if it's actually an upgrade
        $currentPlan = PricePlan::find($currentSubscription->package_id);
        if ($currentPlan !== null && $newPlan->price <= $currentPlan->price) {
            return $this->error('Cannot upgrade to a plan with equal or lower price', 422);
        }

        // Validate coupon if provided
        $coupon = null;
        if (!empty($request->input('coupon_code'))) {
            $couponResult = $this->subscriptionService->validateCoupon(
                $request->input('coupon_code'),
                $newPlan->id,
                $user->id
            );

            if (!$couponResult['valid']) {
                return $this->validationError(['coupon_code' => [$couponResult['message']]]);
            }

            $coupon = $couponResult['coupon'];
        }

        try {
            $result = $this->subscriptionService->upgradeSubscription(
                $currentSubscription,
                $newPlan,
                $coupon
            );

            // Get available payment gateways
            $gateways = PaymentGateway::where('status', true)
                ->select('id', 'name')
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'slug' => strtolower(str_replace(' ', '_', $g->name)),
                ]);

            $requiresPayment = $result['prorated_amount'] > 0;

            return $this->created([
                'payment_log' => new PaymentLogResource($result['payment_log']->load(['package', 'tenant'])),
                'prorated_amount' => $result['prorated_amount'],
                'credit_remaining' => $result['credit_remaining'],
                'requires_payment' => $requiresPayment,
                'payment_gateways' => $gateways,
            ], $requiresPayment ? 'Upgrade initiated. Complete payment to activate.' : 'Upgrade completed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to initiate upgrade: ' . $e->getMessage(), 500);
        }
    }
}

```

Now let me update the API routes:

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\TenantUserAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;
use App\Http\Controllers\Api\V1\Landlord\Admin\AdminController;
use App\Http\Controllers\Api\V1\Landlord\Admin\PricePlanController as AdminPricePlanController;
use App\Http\Controllers\Api\V1\Landlord\Admin\RoleController;
use App\Http\Controllers\Api\V1\Landlord\PlanController;
use App\Http\Controllers\Api\V1\Landlord\SubscriptionController;
use App\Http\Controllers\Api\V1\Landlord\TenantController;
use App\Http\Controllers\Api\V1\Landlord\UserController;
use App\Http\Controllers\Api\V1\Landlord\UserDashboardController;
use App\Http\Controllers\Api\V1\Tenant\TenantInfoController;
use Illuminate\Support\Facades\Route;
```

```php
        // Permissions
        Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');

        /*
        |--------------------------------------------------------------------------
        | Price Plan Management Routes (Admin)
        |--------------------------------------------------------------------------
        */
        Route::prefix('price-plans')->name('price-plans.')->group(function () {
            Route::get('/', [AdminPricePlanController::class, 'index'])->name('index');
            Route::post('/', [AdminPricePlanController::class, 'store'])->name('store');
            Route::get('{id}', [AdminPricePlanController::class, 'show'])->name('show');
            Route::put('{id}', [AdminPricePlanController::class, 'update'])->name('update');
            Route::delete('{id}', [AdminPricePlanController::class, 'destroy'])->name('destroy');
            Route::patch('{id}/toggle-status', [AdminPricePlanController::class, 'toggleStatus'])->name('toggle-status');
            Route::patch('{id}/reorder-features', [AdminPricePlanController::class, 'reorderFeatures'])->name('reorder-features');
        });

        /*
        |--------------------------------------------------------------------------
        | User Management Routes (Admin managing users)
        |--------------------------------------------------------------------------
        */
```

```php
Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Price Plan Routes
    |--------------------------------------------------------------------------
    | Public endpoints for viewing pricing plans.
    | No authentication required for most endpoints.
    */
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('compare', [PlanController::class, 'compare'])->name('compare');
        Route::get('{slug}', [PlanController::class, 'show'])->name('show');

        // Requires authentication
        Route::middleware('auth:api_user')->group(function () {
            Route::post('check-coupon', [PlanController::class, 'checkCoupon'])->name('check-coupon');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Authentication Routes
    |--------------------------------------------------------------------------
    | Routes for platform administrators.
    | Guard: api_admin
    */
    Route::prefix('admin/auth')->name('admin.auth.')->group(function () {
```

```php
        // Payment history
        Route::get('my-payments', [UserDashboardController::class, 'paymentHistory'])->name('my-payments.index');

        /*
        |--------------------------------------------------------------------------
        | Subscription Management Routes
        |--------------------------------------------------------------------------
        | Routes for users to manage their subscriptions.
        */
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('current', [SubscriptionController::class, 'current'])->name('current');
            Route::get('history', [SubscriptionController::class, 'history'])->name('history');
            Route::post('initiate', [SubscriptionController::class, 'initiate'])->name('initiate');
            Route::post('complete', [SubscriptionController::class, 'complete'])->name('complete');
            Route::post('upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade');
            Route::post('{subscriptionId}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
            Route::post('{subscriptionId}/renew', [SubscriptionController::class, 'renew'])->name('renew');
        });
    });
```

Now let me update the PlanFeature model to include the `order` column and check for any issues:

Let me update the models to include the necessary fields:

```php
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_id',
        'feature_name',
        'status',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default ordering by order column.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('order');
        });
    }
```

```php
    /**
     * Plan type constants.
     */
    public const TYPE_MONTHLY = 0;
    public const TYPE_YEARLY = 1;
    public const TYPE_LIFETIME = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'features',
        'type',
        'status',
        'price',
        'free_trial',
        'has_trial',
        'trial_days',
        'page_permission_feature',
        'blog_permission_feature',
        'product_permission_feature',
        'faq',
        'portfolio_permission_feature',
        'zero_price',
        'storage_permission_feature',
        'appointment_permission_feature',
    ];
```

```php
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'integer',
        'status' => 'boolean',
        'price' => 'decimal:2',
        'free_trial' => 'integer',
        'has_trial' => 'boolean',
        'trial_days' => 'integer',
        'zero_price' => 'boolean',
        'page_permission_feature' => 'integer',
        'blog_permission_feature' => 'integer',
        'product_permission_feature' => 'integer',
        'portfolio_permission_feature' => 'integer',
        'storage_permission_feature' => 'integer',
        'appointment_permission_feature' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if the plan has a trial period.
     */
    public function hasTrial(): bool
    {
        return (bool) ($this->has_trial ?? ($this->free_trial > 0));
    }

    /**
     * Get the trial duration in days.
     */
    public function getTrialDays(): int
    {
        return (int) ($this->trial_days ?? $this->free_trial ?? 0);
    }

    /**
     * Get type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_MONTHLY => 'Monthly',
            self::TYPE_YEARLY => 'Yearly',
            self::TYPE_LIFETIME => 'Lifetime',
            default => 'Unknown',
        };
    }
```

Now let me check for linting errors:

Let me store this knowledge for future reference:

I've completed the Price Plan and Subscription management API. Here's a summary of everything created:

## Summary

### 1. Models Created/Updated

| File | Description |
|------|-------------|
| `app/Models/Coupon.php` | New model for coupon management with discount calculation |
| `app/Models/CouponLog.php` | New model to track coupon usage per user |
| `app/Models/PricePlan.php` | Updated with `has_trial`, `trial_days`, type constants, helper methods |
| `app/Models/PlanFeature.php` | Updated with `order` field and global ordering scope |

### 2. Request DTOs (with OpenAPI schemas)

| File | Description |
|------|-------------|
| `app/Http/Requests/PricePlan/StorePricePlanRequest.php` | Create new plan validation |
| `app/Http/Requests/PricePlan/UpdatePricePlanRequest.php` | Update plan validation |
| `app/Http/Requests/PricePlan/ReorderFeaturesRequest.php` | Feature reordering validation |
| `app/Http/Requests/Subscription/InitiateSubscriptionRequest.php` | Start subscription flow |
| `app/Http/Requests/Subscription/CompleteSubscriptionRequest.php` | Complete payment |
| `app/Http/Requests/Subscription/CancelSubscriptionRequest.php` | Cancel with reason |

### 3. Resources (with OpenAPI schemas)

| File | Description |
|------|-------------|
| `app/Http/Resources/PricePlanResource.php` | Updated with full OA schema |
| `app/Http/Resources/PricePlanCollection.php` | New collection with summary |
| `app/Http/Resources/PlanFeatureResource.php` | Updated with OA schema |
| `app/Http/Resources/SubscriptionResource.php` | New subscription resource |
| `app/Http/Resources/CouponResource.php` | New coupon resource |

### 4. Services

| File | Description |
|------|-------------|
| `app/Services/PricePlanService.php` | Plan CRUD, feature sync, comparison matrix |
| `app/Services/SubscriptionService.php` | Full subscription lifecycle with prorated upgrades |

### 5. Controllers

| File | Description |
|------|-------------|
| `app/Http/Controllers/Api/V1/Landlord/Admin/PricePlanController.php` | Admin plan management |
| `app/Http/Controllers/Api/V1/Landlord/PlanController.php` | Public plan endpoints |
| `app/Http/Controllers/Api/V1/Landlord/SubscriptionController.php` | User subscription management |

### 6. API Routes Added

**Public (No Auth):**
- `GET /api/v1/plans` - List active plans
- `GET /api/v1/plans/compare` - Feature comparison matrix
- `GET /api/v1/plans/{slug}` - Plan details

**User Authenticated:**
- `POST /api/v1/plans/check-coupon` - Validate coupon
- `GET /api/v1/subscriptions/current` - Current subscription
- `GET /api/v1/subscriptions/history` - Subscription history
- `POST /api/v1/subscriptions/initiate` - Start subscription
- `POST /api/v1/subscriptions/complete` - Complete payment
- `POST /api/v1/subscriptions/upgrade` - Upgrade with proration
- `POST /api/v1/subscriptions/{id}/renew` - Renew subscription
- `POST /api/v1/subscriptions/{id}/cancel` - Cancel subscription

**Admin Authenticated:**
- `GET /api/v1/admin/price-plans` - List all plans
- `POST /api/v1/admin/price-plans` - Create plan
- `GET /api/v1/admin/price-plans/{id}` - Show plan
- `PUT /api/v1/admin/price-plans/{id}` - Update plan
- `DELETE /api/v1/admin/price-plans/{id}` - Delete plan
- `PATCH /api/v1/admin/price-plans/{id}/toggle-status` - Toggle status
- `PATCH /api/v1/admin/price-plans/{id}/reorder-features` - Reorder features

### Key Features

1. **Plan Types:** Monthly (0), Yearly (1), Lifetime (2)
2. **Coupon Support:** Percentage and fixed amount discounts with usage limits
3. **Prorated Upgrades:** Credits calculated based on remaining days
4. **Trial Support:** Configurable trial days per plan
5. **Payment Flow:** Initiate → Payment Gateway → Complete pattern
6. **Full OpenAPI Documentation:** All endpoints documented with Swagger attributes

---

**User**

Acknowledge this feature for now and don't start anything about it. Keep it in your memory or in this conversation for now until I come back because this is a hard one to follow up.


Create the Payment Gateway integration for handling payments.

Reference:
@app/Http/Controllers/Landlord/Frontend/PaymentLogController.php
@app/Helpers/Payment/PaymentGatewayCredential.php
@app/Models/PaymentGateway.php
Composer package: @composer.json -> xgenious/paymentgateway

Create:

1. app/Http/Controllers/Api/V1/Landlord/Admin/PaymentGatewayController.php
   - index() - list all gateways with status
   - show(gateway) - get gateway config (hide secrets)
   - update(gateway, ConfigRequest) - update gateway credentials
   - toggleStatus(gateway)
   - testConnection(gateway) - verify credentials work

2. app/Http/Controllers/Api/V1/Landlord/PaymentController.php
   - availableGateways() - list enabled gateways for user
   - initiatePayment(gateway, PaymentRequest) - start payment
   - handleCallback(gateway, Request) - OAuth callbacks
   - handleWebhook(gateway, Request) - IPN/Webhook handling
   - verifyPayment(transactionId) - check payment status
   - refund(transactionId, RefundRequest) - process refund

3. app/Services/PaymentService.php
   - getActiveGateways(): Collection
   - initiatePayment(string $gateway, PaymentLog $paymentLog): PaymentResponse
   - processCallback(string $gateway, array $data): PaymentResult
   - processWebhook(string $gateway, array $data): bool
   - verifyTransaction(string $transactionId): TransactionStatus

4. app/Services/PaymentGateways/ (Strategy pattern)
   - PaymentGatewayInterface.php
   - StripeGateway.php
   - PayPalGateway.php
   - RazorpayGateway.php
   - PaystackGateway.php
   - FlutterwaveGateway.php
   - MollieGateway.php
   (one per gateway from old system)

5. app/Http/Requests/Payment/
   - InitiatePaymentRequest.php
   - RefundRequest.php
   - UpdateGatewayConfigRequest.php

6. app/DTOs/
   - PaymentResponse.php
   - PaymentResult.php
   - TransactionStatus.php

7. routes/api.php - Add webhook routes (no auth):
   Route::post('webhooks/payment/{gateway}', [PaymentController::class, 'handleWebhook']);

Reference gateway implementations from:
@app/Http/Controllers/Landlord/Frontend/PaymentLogController.php (stripe_ipn, paypal_ipn, etc.)
@app/Helpers/Payment/PaymentGatewayCredential.php

---

**Cursor**

Acknowledged! I've noted the **Payment Gateway Integration** feature request. Here's a quick summary for when you return:

---

## 📋 Payment Gateway Integration (Pending)

### Scope
Full payment gateway integration using the `xgenious/paymentgateway` package with Strategy pattern for multiple providers.

### Components to Create

| Category | Files |
|----------|-------|
| **Admin Controller** | `Admin/PaymentGatewayController.php` - CRUD, toggle, test connection |
| **User Controller** | `PaymentController.php` - initiate, callback, webhook, verify, refund |
| **Service** | `PaymentService.php` - orchestration layer |
| **Gateway Strategy** | `PaymentGateways/` - Interface + implementations (Stripe, PayPal, Razorpay, Paystack, Flutterwave, Mollie) |
| **Requests** | `InitiatePaymentRequest`, `RefundRequest`, `UpdateGatewayConfigRequest` |
| **DTOs** | `PaymentResponse`, `PaymentResult`, `TransactionStatus` |
| **Routes** | Webhook routes (no auth): `POST /webhooks/payment/{gateway}` |

### Reference Files
- `OLDARCHIVE/app/Http/Controllers/Landlord/Frontend/PaymentLogController.php`
- `OLDARCHIVE/app/Helpers/Payment/PaymentGatewayCredential.php`
- `app/Models/PaymentGateway.php`
- `composer.json` → `xgenious/paymentgateway`

---

When you're ready to continue, just let me know and I'll read through the reference files to understand the existing payment flow and implement the new API structure. This one will integrate nicely with the Subscription system we just built (the `initiatePayment` → `completeSubscription` flow).

---

**User**

Can you suggest branch name and commit message for both branch push and merge branch with main for the feature implementation till now ?

---

**Cursor**

Here are my suggestions for the Price Plan and Subscription Management feature:

---

## Branch Name

```
feature/price-plan-subscription-api
```

**Alternatives:**
- `feature/subscription-management`
- `feature/pricing-plans-api`

---

## Commit Message (for branch push)

```
feat(subscriptions): add price plan and subscription management API

- Add admin price plan CRUD with feature management
- Add public plan endpoints (list, compare, coupon validation)
- Add subscription lifecycle (initiate, complete, renew, upgrade, cancel)
- Add prorated pricing for plan upgrades
- Add Coupon and CouponLog models for discount tracking
- Add comprehensive OpenAPI documentation for all endpoints

Controllers:
- Admin/PricePlanController (CRUD, toggle, reorder features)
- PlanController (public plan listing and comparison)
- SubscriptionController (user subscription management)

Services:
- PricePlanService (plan CRUD, feature sync, comparison matrix)
- SubscriptionService (subscription lifecycle, pricing calculations)
```

---

## Merge Commit Message (when merging to main)

```
Merge feature/price-plan-subscription-api: Price Plan & Subscription Management

Adds complete subscription management system with:
- Admin plan management with feature ordering
- Public pricing pages and plan comparison
- Full subscription lifecycle with prorated upgrades
- Coupon/discount support
- OpenAPI documented endpoints
```

---

## Git Commands

```bash
# Push branch
git push -u origin feature/price-plan-subscription-api

# Create PR (using gh CLI)
gh pr create --title "feat(subscriptions): add price plan and subscription management API" --body "..."

# Or merge locally
git checkout main
git merge --no-ff feature/price-plan-subscription-api -m "Merge feature/price-plan-subscription-api: Price Plan & Subscription Management"
```

Would you like me to suggest anything else for the git workflow?

```plaintext
feature/price-plan-subscription-api
```

```plaintext
feat(subscriptions): add price plan and subscription management API

- Add admin price plan CRUD with feature management
- Add public plan endpoints (list, compare, coupon validation)
- Add subscription lifecycle (initiate, complete, renew, upgrade, cancel)
- Add prorated pricing for plan upgrades
- Add Coupon and CouponLog models for discount tracking
- Add comprehensive OpenAPI documentation for all endpoints

Controllers:
- Admin/PricePlanController (CRUD, toggle, reorder features)
- PlanController (public plan listing and comparison)
- SubscriptionController (user subscription management)

Services:
- PricePlanService (plan CRUD, feature sync, comparison matrix)
- SubscriptionService (subscription lifecycle, pricing calculations)
```

```plaintext
Merge feature/price-plan-subscription-api: Price Plan & Subscription Management

Adds complete subscription management system with:
- Admin plan management with feature ordering
- Public pricing pages and plan comparison
- Full subscription lifecycle with prorated upgrades
- Coupon/discount support
- OpenAPI documented endpoints
```

```shellscript
# Push branch
git push -u origin feature/price-plan-subscription-api

# Create PR (using gh CLI)
gh pr create --title "feat(subscriptions): add price plan and subscription management API" --body "..."

# Or merge locally
git checkout main
git merge --no-ff feature/price-plan-subscription-api -m "Merge feature/price-plan-subscription-api: Price Plan & Subscription Management"
```


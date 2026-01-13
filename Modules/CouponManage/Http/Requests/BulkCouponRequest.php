<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkCouponRequest',
    required: ['action', 'coupon_ids'],
    properties: [
        new OA\Property(
            property: 'action',
            type: 'string',
            enum: ['delete', 'activate', 'deactivate'],
            example: 'activate'
        ),
        new OA\Property(
            property: 'coupon_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
    ]
)]
class BulkCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
            'coupon_ids' => ['required', 'array', 'min:1'],
            'coupon_ids.*' => ['integer', 'exists:product_coupons,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be: delete, activate, or deactivate',
            'coupon_ids.required' => 'Coupon IDs are required',
            'coupon_ids.array' => 'Coupon IDs must be an array',
            'coupon_ids.min' => 'At least one coupon ID is required',
            'coupon_ids.*.integer' => 'Each coupon ID must be an integer',
            'coupon_ids.*.exists' => 'One or more coupon IDs do not exist',
        ];
    }
}

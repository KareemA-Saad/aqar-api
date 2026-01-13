<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateCouponRequest',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Summer Sale Coupon'),
        new OA\Property(property: 'code', type: 'string', example: 'SUMMER2026'),
        new OA\Property(property: 'discount', type: 'string', example: '20'),
        new OA\Property(property: 'discount_type', type: 'string', enum: ['percentage', 'fixed'], example: 'percentage'),
        new OA\Property(property: 'discount_on', type: 'string', example: 'all', nullable: true),
        new OA\Property(property: 'discount_on_details', type: 'string', nullable: true),
        new OA\Property(property: 'expire_date', type: 'string', format: 'date', example: '2026-12-31', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'publish'], example: 'publish'),
    ]
)]
class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('id');
        
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('product_coupons', 'code')->ignore($couponId)],
            'discount' => ['sometimes', 'string', 'max:255'],
            'discount_type' => ['sometimes', 'string', 'in:percentage,fixed'],
            'discount_on' => ['nullable', 'string', 'max:255'],
            'discount_on_details' => ['nullable', 'string'],
            'expire_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:draft,publish'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This coupon code already exists',
            'discount_type.in' => 'Discount type must be either percentage or fixed',
            'status.in' => 'Status must be either draft or publish',
        ];
    }
}

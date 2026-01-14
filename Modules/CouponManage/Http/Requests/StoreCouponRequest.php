<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreCouponRequest',
    required: ['title', 'code', 'discount', 'discount_type'],
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
class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:191', 'unique:product_coupons,code'],
            'discount' => ['required', 'string', 'max:191'],
            'discount_type' => ['required', 'string', 'in:percentage,fixed'],
            'discount_on' => ['required', 'string', 'in:product,category,subcategory,childcategory,order'],
            'discount_on_details' => ['nullable', 'json'],
            'expire_date' => ['required', 'date'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Coupon title is required',
            'title.max' => 'Title must not exceed 191 characters',
            'code.required' => 'Coupon code is required',
            'code.unique' => 'This coupon code already exists',
            'code.max' => 'Code must not exceed 191 characters',
            'discount.required' => 'Discount value is required',
            'discount_type.required' => 'Discount type is required',
            'discount_type.in' => 'Discount type must be either percentage or fixed',
            'discount_on.required' => 'Discount applicability is required',
            'discount_on.in' => 'Discount on must be product, category, subcategory, childcategory, or order',
            'discount_on_details.json' => 'Discount details must be valid JSON',
            'expire_date.required' => 'Expiry date is required',
            'expire_date.date' => 'Expiry date must be a valid date',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be either 0 or 1',
        ];
    }
}

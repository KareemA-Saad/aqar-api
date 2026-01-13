<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidateCouponRequest',
    required: ['code'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'SUMMER2026'),
    ]
)]
class ValidateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Coupon code is required',
        ];
    }
}

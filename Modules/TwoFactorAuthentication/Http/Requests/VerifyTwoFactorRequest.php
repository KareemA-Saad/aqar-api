<?php

declare(strict_types=1);

namespace Modules\TwoFactorAuthentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'VerifyTwoFactorRequest',
    required: ['code'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: '123456', description: 'Six-digit 2FA code'),
    ]
)]
class VerifyTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Two-factor authentication code is required',
            'code.digits' => 'Code must be 6 digits',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\TwoFactorAuthentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DisableTwoFactorRequest',
    required: ['password'],
    properties: [
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123', description: 'Current password for verification'),
    ]
)]
class DisableTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Password is required to disable 2FA',
        ];
    }
}

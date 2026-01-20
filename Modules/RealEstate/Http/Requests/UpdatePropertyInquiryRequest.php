<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Entities\PropertyInquiry;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdatePropertyInquiryRequest',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['new', 'contacted', 'qualified', 'converted', 'closed']),
        new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
        new OA\Property(property: 'agent_id', type: 'integer', nullable: true),
    ]
)]
class UpdatePropertyInquiryRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(PropertyInquiry::$statuses)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}

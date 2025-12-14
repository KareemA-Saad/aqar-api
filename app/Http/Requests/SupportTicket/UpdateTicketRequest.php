<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTicket;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Ticket Request DTO
 *
 * Validates ticket update data (Admin only).
 */
#[OA\Schema(
    schema: 'UpdateTicketRequest',
    title: 'Update Ticket Request',
    properties: [
        new OA\Property(
            property: 'status',
            type: 'integer',
            enum: [0, 1, 2],
            example: 2,
            nullable: true,
            description: '0 = open, 1 = closed, 2 = pending'
        ),
        new OA\Property(
            property: 'priority',
            type: 'integer',
            enum: [0, 1, 2, 3],
            example: 2,
            nullable: true,
            description: '0 = low, 1 = medium, 2 = high, 3 = urgent'
        ),
        new OA\Property(
            property: 'admin_id',
            type: 'integer',
            example: 1,
            nullable: true,
            description: 'Assigned admin ID (null to unassign)'
        ),
        new OA\Property(
            property: 'department_id',
            type: 'integer',
            example: 2,
            nullable: true,
            description: 'Transfer to another department'
        ),
    ]
)]
final class UpdateTicketRequest extends FormRequest
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
            'status' => [
                'sometimes',
                'integer',
                Rule::in(array_keys(SupportTicket::STATUSES)),
            ],
            'priority' => [
                'sometimes',
                'integer',
                Rule::in(array_keys(SupportTicket::PRIORITIES)),
            ],
            'admin_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:admins,id',
            ],
            'department_id' => [
                'sometimes',
                'integer',
                'exists:support_departments,id',
            ],
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
            'status.in' => 'Invalid status. Must be 0 (open), 1 (closed), or 2 (pending).',
            'priority.in' => 'Invalid priority level. Must be 0 (low), 1 (medium), 2 (high), or 3 (urgent).',
            'admin_id.exists' => 'Selected admin does not exist.',
            'department_id.exists' => 'Selected department does not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{status?: int, priority?: int, admin_id?: ?int, department_id?: int}
     */
    public function validatedData(): array
    {
        $data = $this->validated();
        $result = [];

        if (isset($data['status'])) {
            $result['status'] = (int) $data['status'];
        }

        if (isset($data['priority'])) {
            $result['priority'] = (int) $data['priority'];
        }

        if (array_key_exists('admin_id', $data)) {
            $result['admin_id'] = $data['admin_id'] !== null ? (int) $data['admin_id'] : null;
        }

        if (isset($data['department_id'])) {
            $result['department_id'] = (int) $data['department_id'];
        }

        return $result;
    }
}

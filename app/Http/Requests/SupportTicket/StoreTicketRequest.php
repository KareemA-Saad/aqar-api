<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTicket;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Store Ticket Request DTO
 *
 * Validates support ticket creation data.
 */
#[OA\Schema(
    schema: 'StoreTicketRequest',
    title: 'Store Ticket Request',
    required: ['title', 'subject', 'description', 'priority', 'department_id'],
    properties: [
        new OA\Property(
            property: 'title',
            type: 'string',
            example: 'Cannot access my dashboard',
            maxLength: 191
        ),
        new OA\Property(
            property: 'subject',
            type: 'string',
            example: 'Login Issue',
            maxLength: 191
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            example: 'I am unable to login to my dashboard. I keep getting an error message.',
            minLength: 10
        ),
        new OA\Property(
            property: 'priority',
            type: 'integer',
            enum: [0, 1, 2, 3],
            example: 1,
            description: '0 = low, 1 = medium, 2 = high, 3 = urgent'
        ),
        new OA\Property(
            property: 'department_id',
            type: 'integer',
            example: 1,
            description: 'Support department ID'
        ),
        new OA\Property(
            property: 'attachment',
            type: 'string',
            example: 'uploads/tickets/screenshot.png',
            nullable: true,
            description: 'Path to attachment file'
        ),
    ]
)]
final class StoreTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:191'],
            'subject' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'min:10'],
            'priority' => [
                'required',
                'integer',
                Rule::in(array_keys(SupportTicket::PRIORITIES)),
            ],
            'department_id' => [
                'required',
                'integer',
                'exists:support_departments,id',
            ],
            'attachment' => ['nullable', 'string', 'max:500'],
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
            'title.required' => 'Ticket title is required.',
            'subject.required' => 'Subject is required.',
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 10 characters.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Invalid priority level. Must be 0 (low), 1 (medium), 2 (high), or 3 (urgent).',
            'department_id.required' => 'Department is required.',
            'department_id.exists' => 'Selected department does not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{title: string, subject: string, description: string, priority: int, department_id: int, attachment: ?string, via: string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'title' => $data['title'],
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => (int) $data['priority'],
            'department_id' => (int) $data['department_id'],
            'attachment' => $data['attachment'] ?? null,
            'via' => 'api',
        ];
    }
}

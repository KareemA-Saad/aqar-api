<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTicket;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Reply Request DTO
 *
 * Validates ticket reply data.
 */
#[OA\Schema(
    schema: 'TicketReplyRequest',
    title: 'Ticket Reply Request',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Thank you for reaching out. We are looking into this issue.',
            minLength: 5
        ),
        new OA\Property(
            property: 'attachment',
            type: 'string',
            example: 'uploads/tickets/response.pdf',
            nullable: true,
            description: 'Path to attachment file'
        ),
    ]
)]
final class ReplyRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:5'],
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
            'message.required' => 'Reply message is required.',
            'message.min' => 'Reply message must be at least 5 characters.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{message: string, attachment: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'message' => $data['message'],
            'attachment' => $data['attachment'] ?? null,
        ];
    }
}

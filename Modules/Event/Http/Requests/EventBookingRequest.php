<?php

declare(strict_types=1);

namespace Modules\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for creating an event booking.
 */
#[OA\Schema(
    schema: 'EventBookingRequest',
    title: 'Event Booking Request',
    description: 'Request body for booking event tickets',
    required: ['name', 'email', 'phone', 'ticket_qty'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St, City', nullable: true),
        new OA\Property(property: 'ticket_qty', type: 'integer', example: 2, minimum: 1),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'test', nullable: true),
        new OA\Property(property: 'note', type: 'string', example: 'Special requirements', nullable: true),
    ]
)]
final class EventBookingRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'ticket_qty' => ['required', 'integer', 'min:1', 'max:50'],
            'payment_gateway' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
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
            'name.required' => 'Your name is required.',
            'email.required' => 'Your email is required.',
            'email.email' => 'Please provide a valid email address.',
            'phone.required' => 'Your phone number is required.',
            'ticket_qty.required' => 'Number of tickets is required.',
            'ticket_qty.min' => 'You must book at least 1 ticket.',
            'ticket_qty.max' => 'You cannot book more than 50 tickets at once.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for updating an existing event.
 */
#[OA\Schema(
    schema: 'UpdateEventRequest',
    title: 'Update Event Request',
    description: 'Request body for updating an existing event',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Tech Conference 2026', minLength: 2, maxLength: 500),
        new OA\Property(property: 'slug', type: 'string', example: 'tech-conference-2026', nullable: true, maxLength: 500),
        new OA\Property(property: 'content', type: 'string', example: 'Join us for an amazing tech conference...'),
        new OA\Property(property: 'category_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'organizer', type: 'string', example: 'Tech Events Inc', maxLength: 191),
        new OA\Property(property: 'organizer_email', type: 'string', format: 'email', example: 'info@techevents.com'),
        new OA\Property(property: 'organizer_phone', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'venue_location', type: 'string', example: 'Convention Center, New York'),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-06-15', nullable: true),
        new OA\Property(property: 'time', type: 'string', format: 'time', example: '10:00:00', nullable: true),
        new OA\Property(property: 'cost', type: 'number', format: 'double', example: 99.99, nullable: true),
        new OA\Property(property: 'total_ticket', type: 'integer', example: 500, nullable: true),
        new OA\Property(property: 'available_ticket', type: 'integer', example: 320, nullable: true),
        new OA\Property(property: 'image', type: 'string', example: '1', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'meta_title', type: 'string', example: 'SEO Title', nullable: true),
        new OA\Property(property: 'meta_description', type: 'string', example: 'SEO Description', nullable: true),
        new OA\Property(property: 'meta_tags', type: 'string', example: 'tech,conference', nullable: true),
    ]
)]
final class UpdateEventRequest extends FormRequest
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
        $eventId = $this->route('event');

        return [
            'title' => ['sometimes', 'required', 'string', 'min:2', 'max:500'],
            'slug' => ['nullable', 'string', 'max:500', 'unique:events,slug,' . $eventId],
            'content' => ['sometimes', 'required', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:event_categories,id'],
            'organizer' => ['sometimes', 'required', 'string', 'max:191'],
            'organizer_email' => ['sometimes', 'required', 'email', 'max:191'],
            'organizer_phone' => ['sometimes', 'required', 'string', 'max:50'],
            'venue_location' => ['sometimes', 'required', 'string', 'max:500'],
            'date' => ['nullable', 'date'],
            'time' => ['nullable', 'date_format:H:i:s'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'total_ticket' => ['nullable', 'integer', 'min:0'],
            'available_ticket' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:191'],
            'status' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_tags' => ['nullable', 'string', 'max:255'],
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
            'title.required' => 'The event title is required.',
            'title.min' => 'The event title must be at least 2 characters.',
            'content.required' => 'The event content is required.',
            'slug.unique' => 'This slug is already in use.',
            'category_id.exists' => 'The selected category does not exist.',
            'organizer_email.email' => 'The organizer email must be a valid email address.',
            'cost.numeric' => 'The cost must be a number.',
        ];
    }
}

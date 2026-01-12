<?php

declare(strict_types=1);

namespace Modules\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for bulk actions on events.
 */
#[OA\Schema(
    schema: 'BulkEventRequest',
    title: 'Bulk Event Request',
    description: 'Request body for performing bulk actions on events',
    required: ['ids', 'action'],
    properties: [
        new OA\Property(
            property: 'ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(property: 'action', type: 'string', enum: ['delete', 'publish', 'unpublish'], example: 'delete'),
    ]
)]
final class BulkEventRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:events,id'],
            'action' => ['required', 'string', 'in:delete,publish,unpublish'],
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
            'ids.required' => 'Please select at least one event.',
            'ids.array' => 'Invalid data format for event IDs.',
            'ids.min' => 'Please select at least one event.',
            'ids.*.exists' => 'One or more selected events do not exist.',
            'action.required' => 'Please specify an action to perform.',
            'action.in' => 'Invalid action specified.',
        ];
    }
}

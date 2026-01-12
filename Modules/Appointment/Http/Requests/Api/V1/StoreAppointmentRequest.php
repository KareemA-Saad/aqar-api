<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Store Appointment Request
 */
#[OA\Schema(
    schema: 'StoreAppointmentRequest',
    required: ['title', 'price', 'person_type'],
    properties: [
        new OA\Property(property: 'title', type: 'object', example: ['en' => 'Dental Checkup', 'ar' => 'فحص الأسنان']),
        new OA\Property(property: 'description', type: 'object', nullable: true, example: ['en' => 'Full dental examination', 'ar' => 'فحص شامل للأسنان']),
        new OA\Property(property: 'slug', type: 'string', nullable: true, example: 'dental-checkup'),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true),
        new OA\Property(property: 'subcategory_id', type: 'integer', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', minimum: 0, example: 100.00),
        new OA\Property(property: 'person_type', type: 'string', enum: ['single', 'multiple'], example: 'single'),
        new OA\Property(property: 'max_person', type: 'integer', minimum: 1, nullable: true, example: 5),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1], default: 1),
        new OA\Property(property: 'meta_title', type: 'object', nullable: true),
        new OA\Property(property: 'meta_description', type: 'object', nullable: true),
        new OA\Property(property: 'meta_tags', type: 'string', nullable: true),
        new OA\Property(property: 'meta_image', type: 'string', nullable: true),
        new OA\Property(
            property: 'sub_appointments',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'title', type: 'object'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'duration', type: 'integer', description: 'Duration in minutes'),
                ],
                type: 'object'
            )
        ),
        new OA\Property(
            property: 'taxes',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'percentage', type: 'number'),
                ],
                type: 'object'
            )
        ),
    ]
)]
final class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:appointments,slug'],
            'category_id' => ['nullable', 'integer', 'exists:appointment_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:appointment_subcategories,id'],
            'image' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'person_type' => ['required', 'string', 'in:single,multiple'],
            'max_person' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'meta_title' => ['nullable', 'array'],
            'meta_description' => ['nullable', 'array'],
            'meta_tags' => ['nullable', 'string'],
            'meta_image' => ['nullable', 'string'],
            'sub_appointments' => ['nullable', 'array'],
            'sub_appointments.*.title' => ['required_with:sub_appointments', 'array'],
            'sub_appointments.*.title.en' => ['required_with:sub_appointments', 'string'],
            'sub_appointments.*.price' => ['required_with:sub_appointments', 'numeric', 'min:0'],
            'sub_appointments.*.duration' => ['nullable', 'integer', 'min:1'],
            'taxes' => ['nullable', 'array'],
            'taxes.*.name' => ['required_with:taxes', 'string'],
            'taxes.*.percentage' => ['required_with:taxes', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The appointment title is required.',
            'title.en.required' => 'The English title is required.',
            'price.required' => 'The price is required.',
            'price.numeric' => 'The price must be a number.',
            'person_type.required' => 'The person type is required.',
            'person_type.in' => 'The person type must be single or multiple.',
        ];
    }
}

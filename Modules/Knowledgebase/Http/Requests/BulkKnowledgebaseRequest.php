<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkKnowledgebaseRequest',
    required: ['ids', 'action'],
    properties: [
        new OA\Property(
            property: 'ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'action',
            type: 'string',
            enum: ['delete', 'activate', 'deactivate'],
            example: 'delete'
        ),
    ]
)]
class BulkKnowledgebaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:knowledgebases,id'],
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
        ];
    }
}

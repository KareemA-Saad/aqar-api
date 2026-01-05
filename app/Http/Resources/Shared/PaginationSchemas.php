<?php

declare(strict_types=1);

namespace App\Http\Resources\Shared;

use OpenApi\Attributes as OA;

/**
 * Shared pagination schemas for OpenAPI documentation.
 */
#[OA\Schema(
    schema: 'PaginationMeta',
    title: 'Pagination Meta',
    description: 'Pagination metadata for paginated responses',
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'last_page', type: 'integer', example: 10),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'to', type: 'integer', example: 15, nullable: true),
        new OA\Property(property: 'total', type: 'integer', example: 150),
        new OA\Property(property: 'has_more_pages', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    title: 'Pagination Links',
    description: 'Pagination navigation links',
    type: 'object',
    properties: [
        new OA\Property(property: 'first', type: 'string', nullable: true, example: 'https://api.example.com/items?page=1'),
        new OA\Property(property: 'last', type: 'string', nullable: true, example: 'https://api.example.com/items?page=10'),
        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'https://api.example.com/items?page=2'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'Error Response',
    description: 'Standard error response format',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['email' => ['The email field is required.']]
        ),
    ]
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    title: 'Success Response',
    description: 'Standard success response format',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully.'),
    ]
)]
final class PaginationSchemas
{
    // This class serves as a container for OpenAPI schema definitions.
    // The schemas are defined using PHP 8 attributes above the class.
}

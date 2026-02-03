<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Health Check Controller
 */
class HealthController extends Controller
{
    /**
     * API Health Check
     */
    #[OA\Get(
        path: '/api/health',
        summary: 'API Health Check',
        description: 'Check if the API is running and operational',
        tags: ['Health Check']
    )]
    #[OA\Response(
        response: 200,
        description: 'API is healthy',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'AQAR API is running'),
                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2024-12-09T12:00:00.000000Z'),
            ]
        )
    )]
    public function check(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'AQAR API is running',
            'timestamp' => now()->toISOString(),
        ]);
    }
}


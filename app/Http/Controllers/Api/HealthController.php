<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'NuweFarma API')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'JWT')]
class HealthController extends Controller
{
    #[OA\Get(
        path: '/api/health',
        summary: 'Health check',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API is reachable',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'app', type: 'string', example: 'NuweFarma'),
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ]
                )
            ),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'app' => config('app.name', 'Laravel'),
            'status' => 'ok',
        ]);
    }
}

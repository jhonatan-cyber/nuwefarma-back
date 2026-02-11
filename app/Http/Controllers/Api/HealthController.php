<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Health', description: 'Verificación de salud del sistema')]
class HealthController extends Controller
{
    /**
     * Check system health
     */
    #[OA\Get(
        path: '/api/health',
        summary: 'Verificar salud del sistema',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sistema saludable',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'System is healthy'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'status', type: 'string', example: 'healthy'),
                            new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                            new OA\Property(property: 'environment', type: 'string', example: 'local'),
                            new OA\Property(property: 'services', type: 'object', properties: [
                                new OA\Property(property: 'database', type: 'object', properties: [
                                    new OA\Property(property: 'status', type: 'string', example: 'connected'),
                                    new OA\Property(property: 'response_time', type: 'number', example: 1.5),
                                ]),
                                new OA\Property(property: 'cache', type: 'object', properties: [
                                    new OA\Property(property: 'status', type: 'string', example: 'connected'),
                                    new OA\Property(property: 'response_time', type: 'number', example: 0.5),
                                ]),
                            ]),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 503, description: 'Sistema no saludable'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'services' => [],
        ];

        $isHealthy = true;

        // Check database connection
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            $health['services']['database'] = [
                'status' => 'connected',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            $health['services']['database'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $isHealthy = false;
        }

        // Check cache connection
        try {
            $start = microtime(true);
            Cache::put('health_check', 'ok', 60);
            $result = Cache::get('health_check');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            if ($result === 'ok') {
                $health['services']['cache'] = [
                    'status' => 'connected',
                    'response_time' => $responseTime,
                ];
            } else {
                throw new \Exception('Cache read/write test failed');
            }
        } catch (\Exception $e) {
            $health['services']['cache'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $isHealthy = false;
        }

        // Check storage (optional)
        try {
            $testFile = storage_path('app/health_test.tmp');
            file_put_contents($testFile, 'test');
            $canWrite = is_writable($testFile);
            unlink($testFile);
            
            $health['services']['storage'] = [
                'status' => $canWrite ? 'writable' : 'error',
            ];
            
            if (!$canWrite) {
                $isHealthy = false;
            }
        } catch (\Exception $e) {
            $health['services']['storage'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $isHealthy = false;
        }

        $health['status'] = $isHealthy ? 'healthy' : 'unhealthy';
        $statusCode = $isHealthy ? 200 : 503;

        return ApiResponseService::success(
            $health,
            $isHealthy ? 'System is healthy' : 'System has issues',
            $statusCode
        );
    }
}

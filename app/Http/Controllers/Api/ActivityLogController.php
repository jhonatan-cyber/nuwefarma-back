<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Logs de Actividad', description: 'Consulta de registros de auditoría')]
class ActivityLogController extends Controller
{
    #[OA\Get(
        path: '/api/activity-logs',
        summary: 'Listar logs de actividad',
        security: [['bearerAuth' => []]],
        tags: ['Logs de Actividad'],
        parameters: [
            new OA\Parameter(
                name: 'accion',
                in: 'query',
                description: 'Filtrar por acción',
                schema: new OA\Schema(type: 'string', example: 'login')
            ),
            new OA\Parameter(
                name: 'modulo',
                in: 'query',
                description: 'Filtrar por módulo',
                schema: new OA\Schema(type: 'string', example: 'usuarios')
            ),
            new OA\Parameter(
                name: 'usuario_id',
                in: 'query',
                description: 'Filtrar por ID de usuario',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'fecha_desde',
                in: 'query',
                description: 'Fecha desde (Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')
            ),
            new OA\Parameter(
                name: 'fecha_hasta',
                in: 'query',
                description: 'Fecha hasta (Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-31')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Registros por página',
                schema: new OA\Schema(type: 'integer', example: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de logs de actividad',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'usuario_id', type: 'string'),
                                    new OA\Property(property: 'accion', type: 'string'),
                                    new OA\Property(property: 'modulo', type: 'string'),
                                    new OA\Property(property: 'descripcion', type: 'string'),
                                    new OA\Property(property: 'ip_address', type: 'string'),
                                    new OA\Property(property: 'created_at', type: 'string'),
                                    new OA\Property(property: 'usuario', type: 'object'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with('usuario:id,nombre,apellidos,email');

        // Filtros
        if ($accion = $request->query('accion')) {
            $query->where('accion', $accion);
        }

        if ($modulo = $request->query('modulo')) {
            $query->where('modulo', $modulo);
        }

        if ($usuarioId = $request->query('usuario_id')) {
            $query->where('usuario_id', $usuarioId);
        }

        if ($fechaDesde = $request->query('fecha_desde')) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta = $request->query('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        // Ordenar por más reciente
        $query->orderBy('created_at', 'desc');

        // Paginación
        $perPage = $request->query('per_page', 15);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/activity-logs/{id}',
        summary: 'Ver detalles de un log específico',
        security: [['bearerAuth' => []]],
        tags: ['Logs de Actividad'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalles del log',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'usuario_id', type: 'string'),
                                new OA\Property(property: 'accion', type: 'string'),
                                new OA\Property(property: 'modulo', type: 'string'),
                                new OA\Property(property: 'registro_id', type: 'string'),
                                new OA\Property(property: 'descripcion', type: 'string'),
                                new OA\Property(property: 'datos_anteriores', type: 'object'),
                                new OA\Property(property: 'datos_nuevos', type: 'object'),
                                new OA\Property(property: 'ip_address', type: 'string'),
                                new OA\Property(property: 'user_agent', type: 'string'),
                                new OA\Property(property: 'created_at', type: 'string'),
                                new OA\Property(property: 'usuario', type: 'object'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Log no encontrado'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $log = ActivityLog::with('usuario:id,nombre,apellidos,email')->find($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Log no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    #[OA\Get(
        path: '/api/activity-logs/me',
        summary: 'Ver mis propios logs de actividad',
        security: [['bearerAuth' => []]],
        tags: ['Logs de Actividad'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Registros por página',
                schema: new OA\Schema(type: 'integer', example: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mis logs de actividad',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
        ]
    )]
    public function myLogs(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);

        $logs = ActivityLog::where('usuario_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }
}

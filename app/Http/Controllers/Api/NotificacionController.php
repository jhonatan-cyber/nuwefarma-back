<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notificaciones', description: 'Gestión de notificaciones')]
class NotificacionController extends Controller
{
    #[OA\Get(
        path: '/api/notificaciones',
        summary: 'Listar notificaciones',
        security: [['bearerAuth' => []]],
        tags: ['Notificaciones'],
        parameters: [
            new OA\Parameter(name: 'tipo', in: 'query', description: 'Filtrar por tipo', required: false),
            new OA\Parameter(name: 'estado', in: 'query', description: 'Filtrar por estado', required: false),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de notificaciones'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Notificacion::orderBy('created_at', 'desc');

        if ($tipo = $request->query('tipo')) {
            $query->porTipo($tipo);
        }

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        $notificaciones = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notificaciones,
        ]);
    }

    #[OA\Get(
        path: '/api/notificaciones/pendientes',
        summary: 'Obtener notificaciones pendientes',
        security: [['bearerAuth' => []]],
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Notificaciones pendientes'),
        ]
    )]
    public function pendientes(): JsonResponse
    {
        $notificaciones = app(NotificacionService::class)->getNotificacionesPendientes();

        return response()->json([
            'success' => true,
            'data' => $notificaciones,
            'count' => count($notificaciones),
        ]);
    }

    #[OA\Get(
        path: '/api/notificaciones/count',
        summary: 'Contar notificaciones pendientes',
        security: [['bearerAuth' => []]],
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Conteo de notificaciones'),
        ]
    )]
    public function count(): JsonResponse
    {
        $count = app(NotificacionService::class)->getCountPendientes();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    #[OA\Patch(
        path: '/api/notificaciones/{id}/leer',
        summary: 'Marcar notificación como leída',
        security: [['bearerAuth' => []]],
        tags: ['Notificaciones'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notificación marcada como leída'),
        ]
    )]
    public function marcarLeida(string $id): JsonResponse
    {
        $success = app(NotificacionService::class)->marcarComoLeida($id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notificación marcada como leída' : 'Notificación no encontrada',
        ]);
    }

    #[OA\Patch(
        path: '/api/notificaciones/leer-todas',
        summary: 'Marcar todas las notificaciones como leídas',
        security: [['bearerAuth' => []]],
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Todas marcadas como leídas'),
        ]
    )]
    public function marcarTodasLeidas(): JsonResponse
    {
        $count = app(NotificacionService::class)->marcarTodasComoLeidas();

        return response()->json([
            'success' => true,
            'message' => "{$count} notificaciones marcadas como leídas",
        ]);
    }

    #[OA\Post(
        path: '/api/notificaciones/generar-alertas',
        summary: 'Generar alertas automáticas de inventario',
        security: [['bearerAuth' => []]],
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Alertas generadas'),
        ]
    )]
    public function generarAlertas(): JsonResponse
    {
        $resultado = app(NotificacionService::class)->generarTodasLasAlertas();

        return response()->json([
            'success' => true,
            'data' => $resultado,
            'message' => 'Alertas generadas correctamente',
        ]);
    }

    #[OA\Delete(
        path: '/api/notificaciones/{id}',
        summary: 'Eliminar notificación',
        security: [['bearerAuth' => []]],
        tags: ['Notificaciones'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notificación eliminada'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $notificacion = Notificacion::find($id);

        if (! $notificacion) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada',
            ], 404);
        }

        $notificacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada',
        ]);
    }
}

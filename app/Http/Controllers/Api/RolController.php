<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Roles', description: 'Operaciones de gestión de roles')]
class RolController extends Controller
{
    #[OA\Get(
        path: '/api/roles',
        summary: 'Listar todos los roles',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string'), required: false),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de roles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'nombre', type: 'string'),
                                new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                                new OA\Property(property: 'permiso_id', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                                new OA\Property(property: 'estado', type: 'string'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        )),
                        new OA\Property(property: 'count', type: 'integer'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Rol::query();

        $estado = $request->query('estado');
        if ($estado !== null && $estado !== '') {
            $query->where('estado', $estado);
        }

        $roles = $query->get();

        Log::info('Roles index', [
            'params' => $request->all(),
            'query' => $request->query(),
            'estado_used' => $estado,
            'count' => $roles->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $roles,
            'count' => $roles->count(),
        ]);
    }

    #[OA\Post(
        path: '/api/roles',
        summary: 'Crear un nuevo rol',
        tags: ['Roles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'estado'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Administrador'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Rol con acceso total'),
                    new OA\Property(property: 'permiso_id', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                    new OA\Property(property: 'estado', type: 'string', example: 'activo'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Rol creado exitosamente'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255|unique:roles',
                'descripcion' => 'nullable|string',
                'permiso_id' => 'nullable|array',
                'estado' => 'required|string|in:activo,inactivo',
            ]);

            $rol = Rol::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Rol creado exitosamente',
                'data' => $rol,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/roles/{id}',
        summary: 'Ver detalles de un rol',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalles del rol'),
            new OA\Response(response: 404, description: 'Rol no encontrado'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rol,
        ]);
    }

    #[OA\Put(
        path: '/api/roles/{id}',
        summary: 'Editar un rol',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                    new OA\Property(property: 'permiso_id', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                    new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rol actualizado'),
            new OA\Response(response: 404, description: 'Rol no encontrado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:255|unique:roles,nombre,' . $id,
                'descripcion' => 'nullable|string',
                'permiso_id' => 'nullable|array',
                'estado' => 'sometimes|string|in:activo,inactivo',
            ]);

            $rol->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado exitosamente',
                'data' => $rol,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Delete(
        path: '/api/roles/{id}',
        summary: 'Eliminar un rol',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Rol eliminado'),
            new OA\Response(response: 404, description: 'Rol no encontrado'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        }

        $rol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado exitosamente',
        ]);
    }

    #[OA\Get(
        path: '/api/roles/{id}/usuarios-count',
        summary: 'Obtener cantidad de usuarios activos asociados al rol',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cantidad de usuarios'),
            new OA\Response(response: 404, description: 'Rol no encontrado'),
        ]
    )]
    public function getUsuariosCount(string $id): JsonResponse
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        }

        $usuariosActivos = $rol->usuarios()->where('estado', 'activo')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'usuarios_activos' => $usuariosActivos,
            ],
        ]);
    }

    #[OA\Patch(
        path: '/api/roles/{id}/toggle-estado',
        summary: 'Activar o desactivar un rol',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estado del rol actualizado'),
            new OA\Response(response: 404, description: 'Rol no encontrado'),
        ]
    )]
    public function toggleEstado(string $id): JsonResponse
    {
        $rol = Rol::with('usuarios')->find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        }

        $nuevoEstado = $rol->estado === 'activo' ? 'inactivo' : 'activo';
        
        // Contar usuarios activos asociados a este rol
        $usuariosActivos = $rol->usuarios()->where('estado', 'activo')->count();
        
        // Si se está desactivando el rol, desactivar también los usuarios asociados
        if ($nuevoEstado === 'inactivo' && $usuariosActivos > 0) {
            $rol->usuarios()->where('estado', 'activo')->update(['estado' => 'inactivo']);
        }
        
        $rol->update(['estado' => $nuevoEstado]);

        return response()->json([
            'success' => true,
            'message' => $nuevoEstado === 'inactivo' && $usuariosActivos > 0 
                ? "Rol desactivado. Se desactivaron {$usuariosActivos} usuario(s) asociado(s)."
                : 'Estado del rol actualizado',
            'data' => [
                'id' => $rol->id,
                'estado' => $rol->estado,
                'usuarios_afectados' => $nuevoEstado === 'inactivo' ? $usuariosActivos : 0,
            ],
        ]);
    }
}

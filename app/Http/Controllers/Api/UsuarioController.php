<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Usuarios', description: 'Operaciones de gestión de usuarios')]
class UsuarioController extends Controller
{
    #[OA\Get(
        path: '/api/usuarios',
        summary: 'Listar todos los usuarios',
        tags: ['Usuarios'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string'), required: false),
            new OA\Parameter(name: 'rol_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid'), required: false),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de usuarios',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'nombre', type: 'string'),
                                new OA\Property(property: 'apellidos', type: 'string'),
                                new OA\Property(property: 'ci', type: 'string'),
                                new OA\Property(property: 'direccion', type: 'string', nullable: true),
                                new OA\Property(property: 'telefono', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'rol_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'sueldo', type: 'number', format: 'float', nullable: true),
                                new OA\Property(property: 'foto', type: 'string'),
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
        $query = Usuario::with('rol');

        $estado = $request->query('estado');
        if ($estado !== null && $estado !== '') {
            $query->where('estado', $estado);
        }

        $rolId = $request->query('rol_id');
        if ($rolId !== null && $rolId !== '') {
            $query->where('rol_id', $rolId);
        }

        $usuarios = $query->get();

        return response()->json([
            'success' => true,
            'data' => $usuarios,
            'count' => $usuarios->count(),
        ]);
    }

    #[OA\Post(
        path: '/api/usuarios',
        summary: 'Crear un nuevo usuario',
        tags: ['Usuarios'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'apellidos', 'ci', 'telefono', 'email', 'rol_id', 'foto', 'estado'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
                    new OA\Property(property: 'apellidos', type: 'string', example: 'Pérez García'),
                    new OA\Property(property: 'ci', type: 'string', example: '12345678'),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'Av. Siempre Viva 123'),
                    new OA\Property(property: 'telefono', type: 'string', example: '71234567'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan.perez@example.com'),
                    new OA\Property(property: 'rol_id', type: 'string', format: 'uuid', example: '9a7b8c9d-1234-5678-90ab-cdef12345678'),
                    new OA\Property(property: 'sueldo', type: 'number', format: 'float', nullable: true, example: 5000.00),
                    new OA\Property(property: 'foto', type: 'string', example: 'default.jpg'),
                    new OA\Property(property: 'estado', type: 'string', example: 'activo'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado exitosamente'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'ci' => 'required|string|max:20|unique:usuarios',
                'direccion' => 'nullable|string|max:500',
                'telefono' => 'required|string|max:20',
                'email' => 'required|email|unique:usuarios',
                'rol_id' => 'required|uuid|exists:roles,id',
                'sueldo' => 'nullable|numeric|min:0',
                'foto' => 'required|string|max:255',
                'estado' => 'required|string|in:activo,inactivo',
            ]);

            // El modelo automáticamente hasheará el CI como password
            $usuario = Usuario::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $usuario->load('rol'),
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
        path: '/api/usuarios/{id}',
        summary: 'Ver detalles de un usuario',
        tags: ['Usuarios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalles del usuario'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $usuario = Usuario::with('rol')->find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $usuario,
        ]);
    }

    #[OA\Put(
        path: '/api/usuarios/{id}',
        summary: 'Actualizar un usuario',
        tags: ['Usuarios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'apellidos', type: 'string'),
                    new OA\Property(property: 'ci', type: 'string'),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true),
                    new OA\Property(property: 'telefono', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'rol_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'sueldo', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'foto', type: 'string'),
                    new OA\Property(property: 'estado', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado exitosamente'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:255',
                'apellidos' => 'sometimes|string|max:255',
                'ci' => 'sometimes|string|max:20|unique:usuarios,ci,' . $id,
                'direccion' => 'nullable|string|max:500',
                'telefono' => 'sometimes|string|max:20',
                'email' => 'sometimes|email|unique:usuarios,email,' . $id,
                'rol_id' => 'sometimes|uuid|exists:roles,id',
                'sueldo' => 'nullable|numeric|min:0',
                'foto' => 'sometimes|string|max:255',
                'estado' => 'sometimes|string|in:activo,inactivo',
            ]);

            // Si se actualiza el CI, el modelo automáticamente actualizará el password
            $usuario->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $usuario->load('rol'),
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
        path: '/api/usuarios/{id}',
        summary: 'Eliminar un usuario',
        tags: ['Usuarios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario eliminado exitosamente'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $usuario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente',
        ]);
    }

    #[OA\Patch(
        path: '/api/usuarios/{id}/toggle-estado',
        summary: 'Cambiar estado de un usuario (activo/inactivo)',
        tags: ['Usuarios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estado cambiado exitosamente'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function toggleEstado(string $id): JsonResponse
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $usuario->estado = $usuario->estado === 'activo' ? 'inactivo' : 'activo';
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente',
            'data' => $usuario->load('rol'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Categorías', description: 'Gestión de categorías de productos')]
class CategoriaController extends Controller
{
    #[OA\Get(
        path: '/api/categorias',
        summary: 'Listar categorías',
        security: [['bearerAuth' => []]],
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(
                name: 'estado',
                in: 'query',
                description: 'Filtrar por estado',
                schema: new OA\Schema(type: 'string', enum: ['activo', 'inactivo'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de categorías',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'nombre', type: 'string'),
                                    new OA\Property(property: 'descripcion', type: 'string'),
                                    new OA\Property(property: 'estado', type: 'string'),
                                    new OA\Property(property: 'created_at', type: 'string'),
                                    new OA\Property(property: 'updated_at', type: 'string'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Categoria::query();

        // Filtro por estado
        $estado = $request->query('estado');
        if ($estado !== null && $estado !== '') {
            $query->where('estado', $estado);
        }

        $categorias = $query->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $categorias,
        ]);
    }

    #[OA\Post(
        path: '/api/categorias',
        summary: 'Crear nueva categoría',
        security: [['bearerAuth' => []]],
        tags: ['Categorías'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Antibióticos'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Medicamentos antibióticos'),
                    new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo'], example: 'activo'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Categoría creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255|unique:categorias,nombre',
                'descripcion' => 'nullable|string',
                'estado' => 'nullable|in:activo,inactivo',
            ]);

            $categoria = Categoria::create($validated);

            // Registrar actividad
            ActivityLog::registrar(
                accion: 'create',
                modulo: 'categorias',
                registroId: $categoria->id,
                descripcion: "Categoría creada: {$categoria->nombre}",
                datosNuevos: $categoria->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => $categoria,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/categorias/{id}',
        summary: 'Ver detalles de una categoría',
        security: [['bearerAuth' => []]],
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalles de la categoría',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Categoría no encontrada'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $categoria,
        ]);
    }

    #[OA\Put(
        path: '/api/categorias/{id}',
        summary: 'Actualizar categoría',
        security: [['bearerAuth' => []]],
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Antibióticos'),
                    new OA\Property(property: 'descripcion', type: 'string'),
                    new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo']),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoría actualizada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Categoría no encontrada'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:255|unique:categorias,nombre,' . $id,
                'descripcion' => 'nullable|string',
                'estado' => 'sometimes|in:activo,inactivo',
            ]);

            $datosAnteriores = $categoria->toArray();
            $categoria->update($validated);

            // Registrar actividad
            ActivityLog::registrar(
                accion: 'update',
                modulo: 'categorias',
                registroId: $categoria->id,
                descripcion: "Categoría actualizada: {$categoria->nombre}",
                datosAnteriores: $datosAnteriores,
                datosNuevos: $categoria->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => $categoria,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Delete(
        path: '/api/categorias/{id}',
        summary: 'Eliminar categoría',
        security: [['bearerAuth' => []]],
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoría eliminada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Categoría no encontrada'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        $datosAnteriores = $categoria->toArray();
        $categoria->delete();

        // Registrar actividad
        ActivityLog::registrar(
            accion: 'delete',
            modulo: 'categorias',
            registroId: $id,
            descripcion: "Categoría eliminada: {$datosAnteriores['nombre']}",
            datosAnteriores: $datosAnteriores
        );

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente',
        ]);
    }

    #[OA\Patch(
        path: '/api/categorias/{id}/toggle-estado',
        summary: 'Activar/Desactivar categoría',
        security: [['bearerAuth' => []]],
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Categoría no encontrada'),
        ]
    )]
    public function toggleEstado(string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        $estadoAnterior = $categoria->estado;
        $categoria->estado = $categoria->estado === 'activo' ? 'inactivo' : 'activo';
        $categoria->save();

        // Registrar actividad
        ActivityLog::registrar(
            accion: 'toggle_estado',
            modulo: 'categorias',
            registroId: $categoria->id,
            descripcion: "Estado de categoría cambiado: {$categoria->nombre} de {$estadoAnterior} a {$categoria->estado}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente',
            'data' => $categoria,
        ]);
    }
}
